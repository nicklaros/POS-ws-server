<?php

namespace App;

use ORM\PurchaseQuery;
use ORM\SalesQuery;

class Charts
{

    public static function last30DaysTransaction($params, $currentUser, $con)
    {
        $queryDate = new \DateTime();
        $queryDate->sub(new \DateInterval('P30D'));

        $data = [];
        for ($i=1; $i<=30; $i++) {
            $queryDate->add(new \DateInterval('P1D'));

            $sales = SalesQuery::create()
                ->filterByStatus('Active')
                ->filterByDate($queryDate->format('Y-m-d'))
                ->withColumn('SUM(Sales.TotalPrice)', 'sales')
                ->select([
                    'sales'
                ])
                ->find($con);

            $purchase = PurchaseQuery::create()
                ->filterByStatus('Active')
                ->filterByDate($queryDate->format('Y-m-d'))
                ->withColumn('SUM(Purchase.TotalPrice)', 'purchase')
                ->select([
                    'purchase'
                ])
                ->find($con);

            $row = [
                'date' => $queryDate->format('Y-m-d'),
                'sales' => (isset($sales[0]) ? $sales[0] : 0),
                'purchase' => (isset($purchase[0]) ? $purchase[0] : 0)
            ];
            $data[] =  $row;
        }

        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

    public static function monthlyTransaction($params, $currentUser, $con)
    {
        if (!isset($params->month)) throw new \Exception('Missing parameter');
        
        $picked = new \DateTime($params->month);
        $queryDate = new \DateTime($picked->format('Y-m-01'));
        $dayCount = $queryDate->format('t');

        $data = [];
        for ($i=1; $i<=$dayCount; $i++) {
            $sales = SalesQuery::create()
                ->filterByStatus('Active')
                ->filterByDate($queryDate->format('Y-m-d'))
                ->withColumn('SUM(Sales.TotalPrice)', 'sales')
                ->select([
                    'sales'
                ])
                ->find($con);

            $purchase = PurchaseQuery::create()
                ->filterByStatus('Active')
                ->filterByDate($queryDate->format('Y-m-d'))
                ->withColumn('SUM(Purchase.TotalPrice)', 'purchase')
                ->select([
                    'purchase'
                ])
                ->find($con);

            $row = [
                'date' => $queryDate->format('Y-m-d'),
                'sales' => (isset($sales[0]) ? $sales[0] : 0),
                'purchase' => (isset($purchase[0]) ? $purchase[0] : 0)
            ];
            $data[] =  $row;
            
            $queryDate->add(new \DateInterval('P1D'));

        }

        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

    public static function monthlySalesVsPurchase($params, $currentUser, $con)
    {
        if (!isset($params->month)) throw new \Exception('Missing parameter');
        
        $picked = new \DateTime($params->month);
        $start = new \DateTime($picked->format('Y-m-01'));
        $until = new \DateTime($picked->format('Y-m-t'));
        
        $data = [];
        
        $sales = SalesQuery::create()
            ->filterByStatus('Active')
            ->filterByDate(array('min' => $start, 'max' => $until))
            ->withColumn('SUM(Sales.TotalPrice)', 'sales')
            ->select([
                'sales'
            ])
            ->find($con);

        $row = [
            'type' => 'Penjualan',
            'amount' => (isset($sales[0]) ? $sales[0] : 0)
        ];
        $data[] = $row;
        
        $purchase = PurchaseQuery::create()
            ->filterByStatus('Active')
            ->filterByDate(array('min' => $start, 'max' => $until))
            ->withColumn('SUM(Purchase.TotalPrice)', 'purchase')
            ->select([
                'purchase'
            ])
            ->find($con);

        $row = [
            'type' => 'Pembelian',
            'amount' => (isset($purchase[0]) ? $purchase[0] : 0)
        ];
        $data[] = $row;

        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

}