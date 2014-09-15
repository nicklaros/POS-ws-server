<?php

namespace App;

use ORM\Notification;
use ORM\NotificationOnUser;
use ORM\NotificationOnUserQuery;
use ORM\NotificationOptionQuery;
use ORM\RolePermissionQuery;
use ORM\Purchase;
use ORM\PurchaseQuery;
use ORM\PurchaseDetail;
use ORM\PurchaseDetailQuery;
use ORM\PurchaseHistory;
use ORM\StockQuery;
use ORM\UserQuery;

class Purchases
{

    private static function newPriceNotification($stock, $purchaseDetail, $con)
    {
        
        // check price change
        $priceDifference = $stock->getBuy() - ( $purchaseDetail->getTotalPrice() / $purchaseDetail->getAmount() );
        if ( $priceDifference == 0 ) {
            $priceStatus = 'stagnant';
        } elseif ( $priceDifference < 0 ) {
            $priceStatus = 'up';
        } elseif ( $priceDifference > 0 ) {
            $priceStatus = 'down';
        }
        
        // if price is not stagnant then make new notification
        if ( $priceStatus != 'stagnant' ) 
        {
            // build up notification data
            $notificationData = new \stdClass();
            $notificationData->stock_id = $stock->getId();
            $notificationData->status = $priceStatus;
            $notificationData->difference = abs($priceDifference);
            $notificationData->to_price = $stock->getBuy() - $priceDifference;
            
            // update stock buy price
            $stock->setBuy($notificationData->to_price)->save($con);
            
            // check whether price notification for this purchase detail is already there
            $oldNotification = $purchaseDetail->getNotification();
            if ($oldNotification) {
                // if yes, assign the old one
                $isNew = false;
                $notification = $oldNotification;
            } else {
                // if not, create new notification
                $isNew = true;
                $notification = new Notification();
            }
            
            $notification
                ->setTime(time())
                ->setType('price')
                ->setData(json_encode($notificationData))
                ->save($con);
            
            // if notification is new, then give notification to user
            // if not, then update notification's status to unread
            if ($isNew == true) {
                // find which role to send notification
                $roles = NotificationOptionQuery::create()
                    ->filterByType('price')
                    ->find($con);
                
                // iterate through each role to find users assigned to it
                foreach ($roles as $role) 
                {
                    $users = UserQuery::create()
                        ->filterByStatus('Active')
                        ->filterByRoleId($role->getRoleId())
                        ->find($con);
                    
                    // iterate through each user to give notification
                    foreach ($users as $user) 
                    {
                        $notifyUser = new NotificationOnUser();
                        $notifyUser
                            ->setUserId($user->getId())
                            ->setNotificationId($notification->getId())
                            ->save($con);
                    }
                }
            } else {
                $notifyUsers = NotificationOnUserQuery::create()
                    ->filterByNotificationId($notification->getId())
                    ->find($con);
                
                foreach ($notifyUsers as $notifyUser) 
                {
                    $notifyUser
                        ->setStatus('Unread')
                        ->save($con);
                }
            }
            
            $notificationId = $notification->getId();
            
        } else {
            $oldNotification = $purchaseDetail->getNotification();
            if ($oldNotification) {
                $oldNotification->delete($con);
            }
            
            $notificationId = 0;
        }
        
        return $notificationId;
    }

    private static function seeker($params, $currentUser, $con)
    {
        $purchase = PurchaseQuery::create()
            ->leftJoin('Supplier')
            ->filterByStatus('Active')
            ->filterById($params->id)
            ->select(array(
                'id',
                'date',
                'supplier_id',
                'total_price',
                'note'
            ))
            ->withColumn('Supplier.Name', 'supplier_name')
            ->findOne($con);

        if(!$purchase) throw new \Exception("Data tidak ditemukan");

        $purchaseDetails = PurchaseDetailQuery::create()
            ->filterByStatus('Active')
            ->filterByPurchaseId($params->id)
            ->select(array(
                'id',
                'purchase_id',
                'stock_id',
                'amount',
                'total_price'
            ))
            ->useStockQuery()
                ->leftJoin('Product')
                ->leftJoin('Unit')
                ->withColumn('Product.Id', 'product_id')
                ->withColumn('Product.Name', 'product_name')
                ->withColumn('Unit.Name', 'unit_name')
            ->endUse()
            ->find($con);
        
        $detail = [];
        foreach($purchaseDetails as $purchaseDetail) {
            $purchaseDetail['unit_price'] = $purchaseDetail['total_price'] / $purchaseDetail['amount'];
            $detail[] = $purchaseDetail;
        }
        
        $results['data'] = $purchase;
        $results['detail'] = $detail;

        return $results;
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
            if ($stock->getUnlimited() == false) {
                $stock
                    ->setAmount($stock->getAmount() + $product->amount)
                    ->save($con);
            }
            
            $notification = Purchases::newPriceNotification($stock, $purchaseDetail, $con);
            
            $purchaseDetail
                ->setNotificationId($notification)
                ->save($con);
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
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_purchase')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $purchases = PurchaseQuery::create()
            ->filterById($params->id)
            ->find($con);

        if (!$purchases) throw new \Exception('Data tidak ditemukan');

        foreach($purchases as $purchase)
        {
            $purchase
                ->setStatus('Canceled')
                ->save($con);

            $purchaseDetails = PurchaseDetailQuery::create()->filterByPurchaseId($purchase->getId())->find($con);
            
            $detailId = []; 
            foreach($purchaseDetails as $purchaseDetail){
                $purchaseDetail
                    ->setStatus('Canceled')
                    ->save($con);
                
                $notification = $purchaseDetail->getNotification();
                if ($notification){
                    $notification
                        ->setStatus('Not Active')
                        ->save();
                }
                    
                $stock = StockQuery::create()->findOneById($purchaseDetail->getStockId(), $con);
                if ($stock->getUnlimited() == false) {
                    $stock
                        ->setAmount($stock->getAmount() - $purchaseDetail->getAmount())
                        ->save($con);
                }
            
                $detailId[] = $purchaseDetail->getId(); 
            }

            $logData['detailId'] = $detailId;
            
            // log history
            $purchaseHistory = new PurchaseHistory();
            $purchaseHistory
                ->setUserId($currentUser->id)
                ->setPurchaseId($purchase->getId())
                ->setTime(time())
                ->setOperation('cancel')
                ->setData(json_encode($logData))
                ->save($con);
        }

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }
    
    public static function loadFormEdit($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_purchase')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $purchase = Purchases::seeker($params, $currentUser, $con);
        
        $logData['data'] = $purchase['data'];
        $logData['detail'] = $purchase['detail'];
        
        // log history
        $purchaseHistory = new PurchaseHistory();
        $purchaseHistory
            ->setUserId($currentUser->id)
            ->setPurchaseId($params->id)
            ->setTime(time())
            ->setOperation('loadFormEdit')
            ->setData(json_encode($logData))
            ->save($con);
        
        $results['success'] = true;
        $results['data'] = $purchase['data'];
        $results['detail'] = $purchase['detail'];

        return $results;
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
        
        /*
        // log history
        $purchaseHistory = new PurchaseHistory();
        $purchaseHistory
            ->setUserId($currentUser->id)
            ->setPurchaseId(0)
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
        $permission = RolePermissionQuery::create()->select('update_purchase')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $purchase = PurchaseQuery::create()->findOneById($params->id, $con);
        if(!$purchase) throw new \Exception('Data tidak ditemukan');

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
            // check whether current detail iteration is brand new or just updating the old one
            $newDetail = PurchaseDetailQuery::create()->findOneById($product->id);
            if (!$newDetail) {
                $isNew = true;
                $newDetail = new PurchaseDetail();
            } else {
                $isNew = false;
                $oldDetail = $newDetail->copy();
            }
            
            $newDetail
                ->setPurchaseId($purchase->getId())
                ->setStockId($product->stock_id)
                ->setAmount($product->amount)
                ->setTotalPrice($product->total_price)
                ->setStatus('Active')
                ->save($con);

            // make stock dance ^_^
            if ($isNew) {
                $stock = StockQuery::create()->findOneById($newDetail->getStockId(), $con);
                if ($stock->getUnlimited() == false) {
                    $stock
                        ->setAmount($stock->getAmount() + $product->amount)
                        ->save($con);
                }
            } else {
                // check whether updated detail stock is the same old one or not
                if ($newDetail->getStockId() == $oldDetail->getStockId()) 
                {
                    // and if actually the same, then set stock amount like this
                    // amount = currentAmount - oldTransAmount + newTransAmount
                    $stock = StockQuery::create()->findOneById($newDetail->getStockId(), $con);
                    if ($stock->getUnlimited() == false) {
                        $stock
                            ->setAmount($stock->getAmount() - $oldDetail->getAmount() + $newDetail->getAmount())
                            ->save($con);
                    }
                } else {
                    // but if two stocks is not the same,
                    // then take back oldTransAmount from old-stock, and give newTransAmount to new-stock
                    $stock = StockQuery::create()->findOneById($oldDetail->getStockId(), $con);
                    if ($stock->getUnlimited() == false) {
                        $stock
                            ->setAmount($stock->getAmount() - $oldDetail->getAmount())
                            ->save($con);
                    }
                        
                    $stock = StockQuery::create()->findOneById($newDetail->getStockId(), $con);
                    if ($stock->getUnlimited() == false) {
                        $stock
                            ->setAmount($stock->getAmount() + $newDetail->getAmount())
                            ->save($con);
                    }
                }
            }
            
            $notificationId = Purchases::newPriceNotification($stock, $newDetail, $con);
            
            $newDetail
                ->setNotificationId($notificationId)
                ->save($con);
            
        }

        // if there are any sales detail removed then make sure the stocks give back something they own... 'give back amount'
        $removeds = PurchaseDetailQuery::create()
            ->filterById($params->removed_id)
            ->find($con);

        foreach($removeds as $removed){
            $stock = StockQuery::create()->findOneById($removed->getStockId(), $con);
            if ($stock->getUnlimited() == false) {
                $stock
                    ->setAmount($stock->getAmount() - $removed->getAmount()) // sorry I take back my amount.. :p
                    ->save($con);
            }
            
            $removed
                ->setStatus('Deleted')
                ->save($con);
                
            $notification = $removed->getNotification();
            if ($notification){
                $notification->delete($con);
            }
        }
        
        $logData['params'] = $params;
        
        // log history
        $purchaseHistory = new PurchaseHistory();
        $purchaseHistory
            ->setUserId($currentUser->id)
            ->setPurchaseId($params->id)
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

        $purchase = Purchases::seeker($params, $currentUser, $con);
        
        $logData['data'] = $purchase['data'];
        $logData['detail'] = $purchase['detail'];
        
        // log history
        $purchaseHistory = new PurchaseHistory();
        $purchaseHistory
            ->setUserId($currentUser->id)
            ->setPurchaseId($params->id)
            ->setTime(time())
            ->setOperation('viewDetail')
            ->setData(json_encode($logData))
            ->save($con);
        
        $results['success'] = true;
        $results['data'] = $purchase['data'];
        $results['detail'] = $purchase['detail'];

        return $results;
    }

}