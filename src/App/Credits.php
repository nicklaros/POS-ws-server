<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\CreditQuery;
use ORM\CreditPayment;
use ORM\CreditPaymentQuery;

class Credits
{
    
    public static function loadFormPay($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('pay_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $creditPayment = CreditPaymentQuery::create()
            ->filterByStatus('Active')
            ->filterByCreditId($params->credit_id)
            ->useCreditQuery()
                ->useSalesQuery()
                    ->leftJoin('Customer')
                    ->withColumn('Customer.Id', 'customer_id')
                    ->withColumn('Customer.Name', 'customer_name')
                ->endUse()
            ->endUse()
            ->leftJoin('Credit')
            ->withColumn('Credit.Total - SUM(CreditPayment.Paid)', 'credit')
            ->select(array(
                'credit_id',
                'customer_id',
                'customer_name',
                'credit'
            ))
            ->groupBy('CreditPayment.CreditId')
            ->findOne($con);
        
        if (!$creditPayment) throw new \Exception('Data tidak ditemukan.');
        
        if ($creditPayment['credit'] <= 0) throw new \Exception('Piutang sudah terlunasi.');
        
        $results['success'] = true;
        $results['data'] = $creditPayment;

        return $results;
    }
    
    public static function pay($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('pay_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // make sure the credit is still there 
        $creditPayment = CreditPaymentQuery::create()
            ->filterByStatus('Active')
            ->filterByCreditId($params->credit_id)
            ->leftJoin('Credit')
            ->withColumn('Credit.Total - SUM(CreditPayment.Paid)', 'credit')
            ->select(array(
                'credit'
            ))
            ->groupBy('CreditPayment.CreditId')
            ->findOne($con);
        
        if (!$creditPayment) throw new \Exception('Data tidak ditemukan.');
        
        // if credit is already fully paid then stop paying 
        if ($creditPayment <= 0) throw new \Exception('Piutang sudah terlunasi.');
        
        // create new payment
        $creditPayment = new CreditPayment();
        $creditPayment
            ->setDate($params->date)
            ->setCreditId($params->credit_id)
            ->setPaid($params->paid)
            ->setCashierId($params->cashier)
            ->setStatus('Active')
            ->save($con);
        
        $results['success'] = true;
        $results['data'] = 'Yay';

        return $results;
    }
    
    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $credits = CreditQuery::create()
            ->filterByStatus('Active')
            ->useSalesQuery()
                ->leftJoin('Customer')
                ->withColumn('Customer.Id', 'customer_id')
                ->withColumn('Customer.Name', 'customer_name')
                ->withColumn('Sales.Date', 'date')
            ->endUse();
            
        if(isset($params->nota)) $credits->filterById($params->nota);
        if(isset($params->customer)) $credits->useCustomerQuery()->filterByName("%$params->customer%")->endUse();

        $credits = $credits
            ->select(array(
                'id',
                'sales_id',
                'total',
                'customer_id',
                'customer_name',
                'date'
            ));

        foreach($params->sort as $sorter){
            $credits->orderBy($sorter->property, $sorter->direction);
        }
        
        $credits->orderBy('id', 'DESC');
        
        $credits = $credits->paginate($page, $limit);

        $total = $credits->getNbResults();
        
        $data = [];
        foreach($credits as $credit) {
            $payment = CreditPaymentQuery::create()
                ->filterByStatus('Active')
                ->filterByCreditId($credit['id'])
                ->withColumn('SUM(Paid)', 'TotalPaid')
                ->findOne($con);
            
            $credit['paid'] = $payment->getTotalPaid();
            $credit['balance'] = $credit['total'] - $credit['paid'];
            $credit['cash_back'] = ($credit['balance'] < 0 ? abs($credit['balance']) : 0);
            
            $data[] = $credit;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }
    
    public static function readPayment($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $creditPayments = CreditPaymentQuery::create()
            ->filterByStatus('Active')
            ->leftJoin('Cashier')
            ->withColumn('Cashier.Name', 'cashier_name')
            ->useCreditQuery()
                ->useSalesQuery()
                    ->leftJoin('Customer')
                    ->withColumn('Customer.Id', 'customer_id')
                    ->withColumn('Customer.Name', 'customer_name')
                ->endUse()
            ->endUse();
            
        if(isset($params->nota)) $creditPayments->filterById($params->nota);
        if(isset($params->customer)) {
            $creditPayments
                ->useCreditQuery()
                    ->useSalesQuery()
                        ->useCustomerQuery()->filterByName('%' . $params->customer . '%')->endUse()
                    ->endUse()
                ->endUse();
        }

        $creditPayments = $creditPayments
            ->select(array(
                'id',
                'date',
                'credit_id',
                'paid',
                'cashier_id',
                'cashier_name',
                'customer_id',
                'customer_name'
            ));

        foreach($params->sort as $sorter){
            $creditPayments->orderBy($sorter->property, $sorter->direction);
        }
        
        $creditPayments->orderBy('id', 'DESC');
        
        $creditPayments = $creditPayments->paginate($page, $limit);

        $total = $creditPayments->getNbResults();
        
        $data = [];
        foreach($creditPayments as $creditPayment) {
            $data[] = $creditPayment;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

}