<?php

namespace App;

use ORM\PurchaseQuery;
use ORM\SalesQuery;

class Charts
{
    
    private static function getSalesVsPurchase($date, $con)
    {
        $data = [];
        
        $sales = SalesQuery::create()
            ->filterByStatus('Active')
            ->filterByDate(array('min' => $date->start, 'max' => $date->until))
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
            ->filterByDate(array('min' => $date->start, 'max' => $date->until))
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

    public static function customSalesVsPurchase($params, $currentUser, $con)
    {
        if (!isset($params->start) || !isset($params->until)) throw new \Exception('Missing parameter');
        
        $date = new \stdClass();
        $date->start = new \DateTime($params->start);
        $date->until = new \DateTime($params->until);
        
        $results = Charts::getSalesVsPurchase($date, $con);
        
        return $results;
    }

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

    public static function dailyTransaction($params, $currentUser, $con)
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
        
        $date = new \stdClass();
        $date->start = new \DateTime($picked->format('Y-m-01'));
        $date->until = new \DateTime($picked->format('Y-m-t'));
        
        $results = Charts::getSalesVsPurchase($date, $con);
        
        return $results;
    }

}