<?php

namespace App;

use ORM\PurchaseQuery;
use ORM\SalesQuery;

class Reports
{

    public static function monthly($params, $currentUser, $con)
    {
        if (!isset($params->month)) throw new \Exception('Missing parameter');
        
        $picked = new \DateTime($params->month);
        $start = new \DateTime($picked->format('Y-m-01'));
        $until = new \DateTime($picked->format('Y-m-t'));
        
        $sales = SalesQuery::create()
            ->filterByStatus('Active')
            ->filterByDate(array('min' => $start, 'max' => $until))
            ->withColumn('COUNT(Sales.Id)', 'sales_count')
            ->withColumn('SUM(Sales.TotalPrice)', 'sales_total')
            ->select([
                'sales_total',
                'sales_count'
            ])
            ->find($con);

        $data['sales_count'] = (isset($sales[0]['sales_count']) ? $sales[0]['sales_count'] : 0);
        $data['sales_total'] = (isset($sales[0]['sales_total']) ? $sales[0]['sales_total'] : 0);
        
        $purchase = PurchaseQuery::create()
            ->filterByStatus('Active')
            ->filterByDate(array('min' => $start, 'max' => $until))
            ->withColumn('COUNT(Purchase.Id)', 'purchase_count')
            ->withColumn('SUM(Purchase.TotalPrice)', 'purchase_total')
            ->select([
                'purchase_count',
                'purchase_total'
            ])
            ->find($con);

        $data['purchase_count'] = (isset($purchase[0]['purchase_count']) ? $purchase[0]['purchase_count'] : 0);
        $data['purchase_total'] = (isset($purchase[0]['purchase_total']) ? $purchase[0]['purchase_total'] : 0);

        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

}