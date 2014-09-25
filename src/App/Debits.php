<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\DebitQuery;
use ORM\DebitPayment;
use ORM\DebitPaymentQuery;

class Debits
{
    
    public static function cancelPayment($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('pay_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $payment = DebitPaymentQuery::create()
            ->filterById($params->id)
            ->findOne($con);

        if (!$payment) throw new \Exception('Data tidak ditemukan');

        $payment
            ->setStatus('Canceled')
            ->save($con);
        
        $debit = $payment->getDebit();
        $debit
            ->setPaid($debit->getpaid() - $payment->getPaid())
            ->save($con);

        $results['success'] = true;
        $results['data'] = $params->id;

        return $results;
    }
    
    public static function loadFormPay($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('pay_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $debit = DebitQuery::create()
            ->filterByStatus('Active')
            ->filterById($params->debit_id)
            ->usePurchaseQuery()
                ->leftJoin('Supplier')
                ->withColumn('Supplier.Id', 'supplier_id')
                ->withColumn('Supplier.Name', 'supplier_name')
            ->endUse()
            ->withColumn('Debit.Id', 'debit_id')
            ->withColumn('CONVERT(Debit.Total, SIGNED) - CONVERT(Debit.Paid, SIGNED)', 'credit')
            ->select(array(
                'debit_id',
                'supplier_id',
                'supplier_name',
                'credit'
            ))
            ->findOne($con);
        
        if (!$debit) throw new \Exception('Data tidak ditemukan.');
        
        if ($debit['credit'] <= 0) throw new \Exception('Piutang sudah terlunasi.');
        
        $results['success'] = true;
        $results['data'] = $debit;

        return $results;
    }
    
    public static function pay($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('pay_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // make sure the credit is not fully paid already
        $debit = DebitQuery::create()
            ->filterByStatus('Active')
            ->filterById($params->debit_id)
            ->withColumn('CONVERT(Debit.Total, SIGNED) - CONVERT(Debit.Paid, SIGNED)', 'Balance')
            ->findOne($con);
        
        if (!$debit) throw new \Exception('Data tidak ditemukan.');
        
        // if credit is already fully paid then stop paying 
        if ($debit->getBalance() <= 0) throw new \Exception('Piutang ini sudah dilunasi.');
        
        // create new payment
        $debitPayment = new DebitPayment();
        $debitPayment
            ->setDate($params->date)
            ->setDebitId($params->debit_id)
            ->setPaid($params->paid)
            ->setCashierId($params->cashier)
            ->setStatus('Active')
            ->save($con);
        
        $payment = DebitPaymentQuery::create()
            ->filterByStatus('Active')
            ->filterByDebitId($params->debit_id)
            ->withColumn('SUM(Paid)', 'paid')
            ->select(array(
                'paid'
            ))
            ->groupBy('DebitId')
            ->findOne($con);
        
        $debit
            ->setPaid($payment)
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

        $debits = DebitQuery::create()
            ->filterByStatus('Active')
            ->usePurchaseQuery()
                ->leftJoin('Supplier')
                ->withColumn('Supplier.Id', 'supplier_id')
                ->withColumn('Supplier.Name', 'supplier_name')
                ->withColumn('Purchase.Date', 'date')
            ->endUse()
            ->withColumn('CONVERT(Debit.Total, SIGNED) - CONVERT(Debit.Paid, SIGNED)', 'balance');
            
        if(isset($params->id)) $debits->filterById($params->id);
        if(isset($params->purchase_id)) $debits->filterByPurchaseId($params->purchase_id);
        if(isset($params->supplier_id)) {
            $debits
                ->usePurchaseQuery()
                    ->filterBySupplierId($params->supplier_id)
                ->endUse();
        }
        if(isset($params->supplier)) {
            $debits
                ->usePurchaseQuery()
                    ->useSupplierQuery()
                        ->filterByName("%{$params->supplier}%")
                    ->endUse()
                ->endUse();
        }
        if(isset($params->debit_status)){
            switch ($params->debit_status) {
                case 'Lunas':
                    $debits->where('CONVERT(Debit.Total, SIGNED) - CONVERT(Debit.Paid, SIGNED) <= 0');
                    break;
                case 'Belum Lunas':
                    $debits->where('CONVERT(Debit.Total, SIGNED) - CONVERT(Debit.Paid, SIGNED) > 0');
                    break;
            }
        }

        $debits = $debits
            ->select(array(
                'id',
                'purchase_id',
                'total',
                'paid',
                'supplier_id',
                'supplier_name',
                'date',
                'balance'
            ));

        foreach($params->sort as $sorter){
            $debits->orderBy($sorter->property, $sorter->direction);
        }
        
        $debits->orderBy('id', 'DESC');
        
        $debits = $debits->paginate($page, $limit);

        $total = $debits->getNbResults();
        
        $data = [];
        foreach($debits as $debit) {
            $debit = (object) $debit;
            $debit->cash_back = ($debit->balance < 0 ? abs($debit->balance) : 0);
            
            $data[] = $debit;
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

        $debitPayments = DebitPaymentQuery::create()
            ->filterByStatus('Active')
            ->leftJoin('Cashier')
            ->withColumn('Cashier.Name', 'cashier_name')
            ->useDebitQuery()
                ->usePurchaseQuery()
                    ->leftJoin('Supplier')
                    ->withColumn('Supplier.Id', 'supplier_id')
                    ->withColumn('Supplier.Name', 'supplier_name')
                ->endUse()
            ->endUse();
            
        if(isset($params->debit_id)) $debitPayments->filterByDebitId($params->debit_id);
        if(isset($params->supplier)) {
            $debitPayments
                ->useDebitQuery()
                    ->usePurchaseQuery()
                        ->useSupplierQuery()
                            ->filterByName('%' . $params->supplier . '%')
                        ->endUse()
                    ->endUse()
                ->endUse();
        }
        if(isset($params->start_date)) $debitPayments->filterByDate(array('min' => $params->start_date));
        if(isset($params->until_date)) $debitPayments->filterByDate(array('max' => $params->until_date));

        $debitPayments = $debitPayments
            ->select(array(
                'id',
                'date',
                'debit_id',
                'paid',
                'cashier_id',
                'cashier_name',
                'supplier_id',
                'supplier_name'
            ));

        foreach($params->sort as $sorter){
            $debitPayments->orderBy($sorter->property, $sorter->direction);
        }
        
        $debitPayments->orderBy('id', 'DESC');
        
        $debitPayments = $debitPayments->paginate($page, $limit);

        $total = $debitPayments->getNbResults();
        
        $data = [];
        foreach($debitPayments as $debitPayment) {
            $data[] = $debitPayment;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

}