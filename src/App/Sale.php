<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\Sales;
use ORM\SalesQuery;
use ORM\SalesDetail;
use ORM\StockQuery;

class Sale
{

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_sales')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // create new sales
        $sales = new Sales();
        $sales
            ->setDate($params->date)
            ->setCustomerId($params->customer_id)
            ->setBuyPrice($params->buy_price)
            ->setTotalPrice($params->total_price)
            ->setPaid($params->paid)
            ->setCashierId($params->cashier_id)
            ->setNote($params->note)
            ->setStatus('Active')
            ->save($con);

        $products = json_decode($params->products);
        foreach ($products as $product)
        {
            // create new record representing product stock which is being saled
            $salesDetail = new SalesDetail();
            $salesDetail
                ->setSalesId($sales->getId())
                ->setType($product->type)
                ->setStockId($product->stock_id)
                ->setAmount($product->amount)
                ->setUnitId($product->unit_id)
                ->setUnitPrice($product->unit_price)
                ->setDiscount($product->discount)
                ->setTotalPrice($product->total_price)
                ->setBuy($product->buy)
                ->setSellPublic($product->sell_public)
                ->setSellDistributor($product->sell_distributor)
                ->setSellMisc($product->sell_misc)
                ->setStatus('Active')
                ->save($con);

            // substract stock 
            $stock = StockQuery::create()->findOneById($product->stock_id, $con);
            $stock
                ->setAmount($stock->getAmount() - $product->amount)
                ->save($con);

        }

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($sales->getId())
            ->setData('sales')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['id'] = $sales->getId();

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
    }

    public static function loadFormEdit($params, $currentUser, $con)
    {
    }

    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_sales')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $sales = SalesQuery::create()
            ->leftJoin('Customer')
            ->leftJoin('Cashier')
            ->filterByStatus('Active');
            
        if(isset($params->code)) $sales->useProductQuery()->filterByCode("%$params->code%")->endUse();
        if(isset($params->product)) $sales->useProductQuery()->filterByName("%$params->product%")->endUse();

        $sales = $sales
            ->select(array(
                'id',
                'date',
                'customer_id',
                'buy_price',
                'total_price',
                'paid',
                'cashier_id',
                'note'
            ))
            ->withColumn('CAST(Sales.Paid AS SIGNED) - CAST(Sales.TotalPrice AS SIGNED)', 'balance')
            ->withColumn('Customer.Name', 'customer_name')
            ->withColumn('Cashier.Name', 'cashier_name');

        foreach($params->sort as $sorter){
            $sales->orderBy($sorter->property, $sorter->direction);
        }
        
        $sales->orderBy('id', 'DESC');
        
        $sales = $sales->paginate($page, $limit);

        $total = $sales->getNbResults();
        
        $data = [];
        foreach($sales as $row) {
            $data[] = $row;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
    }

}