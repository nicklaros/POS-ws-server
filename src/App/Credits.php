<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\CreditQuery;
use ORM\CreditPayment;
use ORM\CreditPaymentQuery;

class Credits
{
    
    public static function cancelPayment($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('pay_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $payment = CreditPaymentQuery::create()
            ->filterById($params->id)
            ->findOne($con);

        if (!$payment) throw new \Exception('Data tidak ditemukan');

        $payment
            ->setStatus('Canceled')
            ->save($con);
        
        $credit = $payment->getCredit();
        $credit
            ->setPaid($credit->getpaid() - $payment->getPaid())
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

        $credit = CreditQuery::create()
            ->filterByStatus('Active')
            ->filterById($params->credit_id)
            ->useSalesQuery()
                ->leftJoin('SecondParty')
                ->withColumn('SecondParty.Id', 'second_party_id')
                ->withColumn('SecondParty.Name', 'second_party_name')
            ->endUse()
            ->withColumn('Credit.Id', 'credit_id')
            ->withColumn('CONVERT(Credit.Total, SIGNED) - CONVERT(Credit.Paid, SIGNED)', 'credit')
            ->select(array(
                'credit_id',
                'second_party_id',
                'second_party_name',
                'credit'
            ))
            ->findOne($con);
        
        if (!$credit) throw new \Exception('Data tidak ditemukan.');
        
        if ($credit['credit'] <= 0) throw new \Exception('Piutang sudah terlunasi.');
        
        $results['success'] = true;
        $results['data'] = $credit;

        return $results;
    }
    
    public static function pay($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('pay_credit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // make sure the credit is not fully paid already
        $credit = CreditQuery::create()
            ->filterByStatus('Active')
            ->filterById($params->credit_id)
            ->withColumn('CONVERT(Credit.Total, SIGNED) - CONVERT(Credit.Paid, SIGNED)', 'Balance')
            ->findOne($con);
        
        if (!$credit) throw new \Exception('Data tidak ditemukan.');
        
        // if credit is already fully paid then stop paying 
        if ($credit->getBalance() <= 0) throw new \Exception('Piutang ini sudah dilunasi.');
        
        // create new payment
        $creditPayment = new CreditPayment();
        $creditPayment
            ->setDate($params->date)
            ->setCreditId($params->credit_id)
            ->setPaid($params->paid)
            ->setCashierId($params->cashier)
            ->setStatus('Active')
            ->save($con);
        
        $payment = CreditPaymentQuery::create()
            ->filterByStatus('Active')
            ->filterByCreditId($params->credit_id)
            ->withColumn('SUM(Paid)', 'paid')
            ->select(array(
                'paid'
            ))
            ->groupBy('CreditId')
            ->findOne($con);
        
        $credit
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

        $credits = CreditQuery::create()
            ->filterByStatus('Active')
            ->useSalesQuery()
                ->leftJoin('SecondParty')
                ->withColumn('SecondParty.Id', 'second_party_id')
                ->withColumn('SecondParty.Name', 'second_party_name')
                ->withColumn('Sales.Date', 'date')
            ->endUse()
            ->withColumn('CONVERT(Credit.Total, SIGNED) - CONVERT(Credit.Paid, SIGNED)', 'balance');
            
        if(isset($params->id)) $credits->filterById($params->id);
        if(isset($params->sales_id)) $credits->filterBySalesId($params->sales_id);
        if(isset($params->second_party_id)) {
            $credits
                ->useSalesQuery()
                    ->filterBySecondPartyId($params->second_party_id)
                ->endUse();
        }
        if(isset($params->second_party)) {
            $credits
                ->useSalesQuery()
                    ->useSecondPartyQuery()
                        ->filterByName("%{$params->second_party}%")
                    ->endUse()
                ->endUse();
        }
        if(isset($params->credit_status)){
            switch ($params->credit_status) {
                case 'Lunas':
                    $credits->where('CONVERT(Credit.Total, SIGNED) - CONVERT(Credit.Paid, SIGNED) <= 0');
                    break;
                case 'Belum Lunas':
                    $credits->where('CONVERT(Credit.Total, SIGNED) - CONVERT(Credit.Paid, SIGNED) > 0');
                    break;
            }
        }

        $credits = $credits
            ->select(array(
                'id',
                'sales_id',
                'total',
                'paid',
                'second_party_id',
                'second_party_name',
                'date',
                'balance'
            ));

        foreach($params->sort as $sorter){
            $credits->orderBy($sorter->property, $sorter->direction);
        }
        
        $credits->orderBy('id', 'DESC');
        
        $credits = $credits->paginate($page, $limit);

        $total = $credits->getNbResults();
        
        $data = [];
        foreach($credits as $credit) {
            $credit = (object) $credit;
            $credit->cash_back = ($credit->balance < 0 ? abs($credit->balance) : 0);
            
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
                    ->leftJoin('SecondParty')
                    ->withColumn('SecondParty.Id', 'second_party_id')
                    ->withColumn('SecondParty.Name', 'second_party_name')
                ->endUse()
            ->endUse();
            
        if(isset($params->credit_id)) $creditPayments->filterByCreditId($params->credit_id);
        if(isset($params->second_party)) {
            $creditPayments
                ->useCreditQuery()
                    ->useSalesQuery()
                        ->useSecondPartyQuery()
                            ->filterByName('%' . $params->second_party . '%')
                        ->endUse()
                    ->endUse()
                ->endUse();
        }
        if(isset($params->start_date)) $creditPayments->filterByDate(array('min' => $params->start_date));
        if(isset($params->until_date)) $creditPayments->filterByDate(array('max' => $params->until_date));

        $creditPayments = $creditPayments
            ->select(array(
                'id',
                'date',
                'credit_id',
                'paid',
                'cashier_id',
                'cashier_name',
                'second_party_id',
                'second_party_name'
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