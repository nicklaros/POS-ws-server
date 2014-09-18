<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\Credit;
use ORM\CreditQuery;
use ORM\Sales;
use ORM\SalesQuery;
use ORM\SalesDetail;
use ORM\SalesDetailQuery;
use ORM\SalesHistory;
use ORM\StockQuery;

class Sale
{

    private static function seeker($params, $currentUser, $con)
    {
        $sales = SalesQuery::create()
            ->leftJoin('Customer')
            ->leftJoin('Cashier')
            ->filterByStatus('Active')
            ->filterById($params->id)
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
            ->withColumn('Cashier.Name', 'cashier_name')
            ->findOne($con);

        if(!$sales) throw new \Exception("Data tidak ditemukan");

        $salesDetails = SalesDetailQuery::create()
            ->filterByStatus('Active')
            ->filterBySalesId($params->id)
            ->useStockQuery()
                ->leftJoin('Product')
                ->leftJoin('Unit')
                ->withColumn('Product.Name', 'product_name')
                ->withColumn('Unit.Id', 'unit_id')
                ->withColumn('Unit.Name', 'unit_name')
            ->endUse()
            ->select(array(
                'id',
                'sales_id',
                'type',
                'stock_id',
                'amount',
                'unit_price',
                'discount',
                'total_price',
                'buy',
                'sell_public',
                'sell_distributor',
                'sell_misc'
            ))
            ->find($con);
        
        $detail = [];
        foreach($salesDetails as $salesDetail) {
            $salesDetail['total_buy_price'] = $salesDetail['buy'] * $salesDetail['amount'];
            $salesDetail['total_price_wo_discount'] = $salesDetail['unit_price'] * $salesDetail['amount'];
            $detail[] = $salesDetail;
        }
        
        $results['data'] = $sales;
        $results['detail'] = $detail;

        return $results;
    }

    public static function cancel($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_sales')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $sales = SalesQuery::create()
            ->filterById($params->id)
            ->find($con);

        if (!$sales) throw new \Exception('Data tidak ditemukan');

        foreach($sales as $sale)
        {
            $sale
                ->setStatus('Canceled')
                ->save($con);

            $credit = CreditQuery::create()
                ->filterBySalesId($sale->getId())
                ->findOne($con);
            
            if ($credit) {
                $credit
                    ->setStatus('Canceled')
                    ->save($con);
            }
            
            $salesDetails = SalesDetailQuery::create()->filterBySalesId($sale->getId())->find($con);
            
            $detailId = []; 
            foreach($salesDetails as $salesDetail){
                $salesDetail
                    ->setStatus('Canceled')
                    ->save($con);
                    
                $stock = StockQuery::create()->findOneById($salesDetail->getStockId(), $con);
                if ($stock->getUnlimited() == false) {
                    $stock
                        ->setAmount($stock->getAmount() + $salesDetail->getAmount())
                        ->save($con);
                }
            
                $detailId[] = $salesDetail->getId(); 
            }

            $logData['detailId'] = $detailId;
            
            // log history
            $salesHistory = new SalesHistory();
            $salesHistory
                ->setUserId($currentUser->id)
                ->setSalesId($sale->getId())
                ->setTime(time())
                ->setOperation('cancel')
                ->setData(json_encode($logData))
                ->save($con);
        }

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

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
        
        // check wether this transaction is credit or not
        $balance = $params->paid - $params->total_price;
        
        if ($balance < 0) {
            $credit = new Credit();
            $credit
                ->setSalesId($sales->getId())
                ->setCustomerId($sales->getCustomerId())
                ->setTotal(abs($balance))
                ->setStatus('Active')
                ->save($con);
        }

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
            if ($stock->getUnlimited() == false) {
                $stock
                    ->setAmount($stock->getAmount() - $product->amount)
                    ->save($con);
            }
        }
        

        $logData['params'] = $params;
        
        // log history
        $salesHistory = new SalesHistory();
        $salesHistory
            ->setUserId($currentUser->id)
            ->setSalesId($sales->getId())
            ->setTime(time())
            ->setOperation('create')
            ->setData(json_encode($logData))
            ->save($con);

        $results['success'] = true;
        $results['id'] = $sales->getId();

        return $results;
    }
    
    public static function loadFormEdit($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_sales')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $sales = Sale::seeker($params, $currentUser, $con);
        
        $logData['data'] = $sales['data'];
        $logData['detail'] = $sales['detail'];
        
        // log history
        $salesHistory = new SalesHistory();
        $salesHistory
            ->setUserId($currentUser->id)
            ->setSalesId($params->id)
            ->setTime(time())
            ->setOperation('loadFormEdit')
            ->setData(json_encode($logData))
            ->save($con);
        
        $results['success'] = true;
        $results['data'] = $sales['data'];
        $results['detail'] = $sales['detail'];

        return $results;
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
            
        if(isset($params->id)) $sales->filterById($params->id);
        if(isset($params->customer)) $sales->useCustomerQuery()->filterByName('%' . $params->customer . '%')->endUse();
        if(isset($params->start_date)) $sales->filterByDate(array('min' => $params->start_date));
        if(isset($params->until_date)) $sales->filterByDate(array('max' => $params->until_date));
        if(isset($params->payment_status)){
            switch ($params->payment_status) {
                case 'Lunas':
                    $sales->where('CONVERT(Sales.TotalPrice, SIGNED) - CONVERT(Sales.Paid, SIGNED) <= 0');
                    break;
                case 'Belum Lunas':
                    $sales->where('CONVERT(Sales.TotalPrice, SIGNED) - CONVERT(Sales.Paid, SIGNED) > 0');
                    break;
            }
        }

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
        $resultId = [];
        foreach($sales as $sale) {
            $data[] = $sale;
            $resultId[] = $sale['id'];
        }
        
        $logData['params'] = $params;
        $logData['resultId'] = $resultId;
        
        /*
        // log history
        $salesHistory = new SalesHistory();
        $salesHistory
            ->setUserId($currentUser->id)
            ->setSalesId(0)
            ->setTime(time())
            ->setOperation('read')
            ->setData(json_encode($logData))
            ->save($con);
        */
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_sales')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $sales = SalesQuery::create()->findOneById($params->id, $con);
        if(!$sales) throw new \Exception('Data tidak ditemukan');

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
        
        // check wether this transaction is credit or not
        $balance = $params->paid - $params->total_price;
        
        if ($balance < 0) {
            $credit = CreditQuery::create()
                ->filterBySalesId($sales->getId())
                ->findOne($con);
            
            if (!$credit) {
                $credit = new Credit();
            }
            
            $credit
                ->setSalesId($sales->getId())
                ->setCustomerId($sales->getCustomerId())
                ->setTotal(abs($balance))
                ->setStatus('Active')
                ->save($con);
            
        } else {
            $credit = CreditQuery::create()
                ->filterBySalesId($sales->getId())
                ->findOne($con);
            
            if ($credit) {
                $credit
                    ->setSalesId($sales->getId())
                    ->setCustomerId($sales->getCustomerId())
                    ->setTotal(0)
                    ->setStatus('Canceled')
                    ->save($con);
            }
        }
        
        $products = json_decode($params->products);
        
        // iterate through every product on this sales operation
        foreach ($products as $product){
            $newDetail = SalesDetailQuery::create()->findOneById($product->id);

            // check whether current detail iteration is brand new or just updating the old one
            if (!$newDetail) {
                $isNew = true;
                $newDetail = new SalesDetail();
            } else {
                $isNew = false;
                $oldDetail = $newDetail->copy();
            }
            
            $newDetail
                ->setSalesId($sales->getId())
                ->setType($product->type)
                ->setStockId($product->stock_id)
                ->setAmount($product->amount)
                ->setUnitPrice($product->unit_price)
                ->setDiscount($product->discount)
                ->setTotalPrice($product->total_price)
                ->setBuy($product->buy)
                ->setSellPublic($product->sell_public)
                ->setSellDistributor($product->sell_distributor)
                ->setSellMisc($product->sell_misc)
                ->setStatus('Active')
                ->save($con);

            // make stock dance ^_^
            if ($isNew) {
                $stock = StockQuery::create()->findOneById($newDetail->getStockId(), $con);
                if ($stock->getUnlimited() == false) {
                    $stock
                        ->setAmount($stock->getAmount() - $newDetail->getAmount())
                        ->save($con);
                } 
                    
            } else {
                // check whether updated detail stock is the same old one or not
                if ($newDetail->getStockId() == $oldDetail->getStockId()) {
                
                    // and if actually the same, then set stock amount like this
                    // amount = currentAmount + oldTransAmount - newTransAmount
                    $stock = StockQuery::create()->findOneById($newDetail->getStockId(), $con);
                    if ($stock->getUnlimited() == false) {
                        $stock
                            ->setAmount($stock->getAmount() + $oldDetail->getAmount() - $newDetail->getAmount())
                            ->save($con);
                    }
                        
                } else {
                    // but if two stocks is not the same,
                    // then give back oldTransAmount to old-stock, and take newTransAmount from new-stock
                    $stock = StockQuery::create()->findOneById($oldDetail->getStockId(), $con);
                    if ($stock->getUnlimited() == false) {
                        $stock
                            ->setAmount($stock->getAmount() + $oldDetail->getAmount())
                            ->save($con);
                    }
                    
                    $stock = StockQuery::create()->findOneById($newDetail->getStockId(), $con);
                    if ($stock->getUnlimited() == false) {
                        $stock
                            ->setAmount($stock->getAmount() - $newDetail->getAmount())
                            ->save($con);
                    }
                }
            }
        }

        // if there are any sales detail removed then make sure the stocks get what it deserve... 'gimme amount'
        $removeds = SalesDetailQuery::create()
            ->filterById($params->removed_id)
            ->find($con);

        foreach($removeds as $removed){
            $stock = StockQuery::create()->findOneById($removed->getStockId(), $con);
            if ($stock->getUnlimited() == false) {
                $stock
                    ->setAmount($stock->getAmount() + $removed->getAmount()) // there you got your amount.. happy now?
                    ->save($con);
            }
                
            $removed
                ->setStatus('Deleted')
                ->save($con);
        }
        
        $logData['params'] = $params;
        
        // log history
        $salesHistory = new SalesHistory();
        $salesHistory
            ->setUserId($currentUser->id)
            ->setSalesId($params->id)
            ->setTime(time())
            ->setOperation('update')
            ->setData(json_encode($logData))
            ->save($con);

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

    public static function viewDetail($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_sales')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $sales = Sale::seeker($params, $currentUser, $con);
        
        $logData['data'] = $sales['data'];
        $logData['detail'] = $sales['detail'];
        
        // log history
        $salesHistory = new SalesHistory();
        $salesHistory
            ->setUserId($currentUser->id)
            ->setSalesId($params->id)
            ->setTime(time())
            ->setOperation('viewDetail')
            ->setData(json_encode($logData))
            ->save($con);
        
        $results['success'] = true;
        $results['data'] = $sales['data'];
        $results['detail'] = $sales['detail'];

        return $results;
    }

}