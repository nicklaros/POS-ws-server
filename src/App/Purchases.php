<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\Purchase;
use ORM\PurchaseQuery;
use ORM\PurchaseDetail;
use ORM\PurchaseDetailQuery;
use ORM\PurchaseHistory;
use ORM\StockQuery;

class Purchases
{

    private static function seeker($params, $currentUser, $con)
    {
    }

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_purchase')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // create new purchase
        $purchase = new Purchase();
        $purchase
            ->setDate($params->date)
            ->setSupplierId($params->supplier_id)
            ->setTotalPrice($params->total_price)
            ->setNote($params->note)
            ->setStatus('Active')
            ->save($con);

        $products = json_decode($params->products);
        
        foreach ($products as $product)
        {
            // create new record representing product stock which is being purchased
            $purchaseDetail = new PurchaseDetail();
            $purchaseDetail
                ->setPurchaseId($purchase->getId())
                ->setStockId($product->stock_id)
                ->setAmount($product->amount)
                ->setTotalPrice($product->total_price)
                ->setStatus('Active')
                ->save($con);

            // add stock amount
            $stock = StockQuery::create()->findOneById($product->stock_id, $con);
            if ($stock) {
                $stock
                    ->setAmount($stock->getAmount() + $product->amount)
                    ->save($con);
            }
        }        

        $logData['params'] = $params;
        
        // log history
        $purchaseHistory = new PurchaseHistory();
        $purchaseHistory
            ->setUserId($currentUser->id)
            ->setPurchaseId($purchase->getId())
            ->setTime(time())
            ->setOperation('create')
            ->setData(json_encode($logData))
            ->save($con);

        $results['success'] = true;
        $results['id'] = $purchase->getId();

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
    }
    
    public static function loadFormEdit($params, $currentUser, $con)
    {
    }
    
    public static function viewDetail($params, $currentUser, $con)
    {
    }

    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_purchase')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $purchases = PurchaseQuery::create()
            ->leftJoin('Supplier')
            ->filterByStatus('Active');
            
        if(isset($params->code)) $purchases->filterById($params->code);
        if(isset($params->supplier)) $purchases->where('Supplier.Name like ?', '%' . $params->supplier . '%');

        $purchases = $purchases
            ->select(array(
                'id',
                'date',
                'supplier_id',
                'total_price',
                'note'
            ))
            ->withColumn('Supplier.Name', 'supplier_name');

        foreach($params->sort as $sorter){
            $purchases->orderBy($sorter->property, $sorter->direction);
        }
        
        $purchases->orderBy('id', 'DESC');
        
        $purchases = $purchases->paginate($page, $limit);

        $total = $purchases->getNbResults();
        
        $data = [];
        $resultId = [];
        foreach($purchases as $purchase) {
            $data[] = $purchase;
            $resultId[] = $purchase['id'];
        }
        
        $logData['params'] = $params;
        $logData['resultId'] = $resultId;
        
        // log history
        $purchaseHistory = new PurchaseHistory();
        $purchaseHistory
            ->setUserId($currentUser->id)
            ->setPurchaseId(0)
            ->setTime(time())
            ->setOperation('read')
            ->setData(json_encode($logData))
            ->save($con);
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
    }

}