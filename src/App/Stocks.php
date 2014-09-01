<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\Stock;
use ORM\StockQuery;

class Stocks
{

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_stock')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // create new stock
        $stock = new Stock();
        $stock
            ->setProductId($params->product_id)
            ->setAmount($params->amount)
            ->setUnit($params->unit)
            ->setBuy($params->buy)
            ->setSellPublic($params->sell_public)
            ->setSellDistributor($params->sell_distributor)
            ->setSellMisc($params->sell_misc)
            ->setDiscount($params->discount)
            ->save($con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($stock->getId())
            ->setData('stock')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['id'] = $stock->getId();

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
        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $stock = StockQuery::create();

        if(isset($params->code)) $stock->useProductQuery()->filterByCode("%$params->code%")->endUse();
        if(isset($params->name)) $stock->useProductQuery()->filterByName("%$params->name%")->endUse();

        $stock = $stock
            ->select(array(
                'id',
                'amount',
                'unit',
                'buy',
                'sell_public',
                'sell_distributor',
                'sell_misc',
                'discount',
            ))
            ->leftJoin('Product')
            ->withColumn('Product.Code', 'code')
            ->withColumn('Product.Name', 'name');

        foreach($params->sort as $sorter){
            $stock->orderBy($sorter->property, $sorter->direction);
        }

        $stock = $stock->paginate($page, $limit);

        $total = $stock->getNbResults();
        
        $data = [];
        foreach($stock as $row) {
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