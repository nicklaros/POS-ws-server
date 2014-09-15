<?php

namespace App;

use ORM\PurchaseQuery;
use ORM\PurchaseDetailQuery;
use ORM\SalesQuery;
use ORM\SalesDetailQuery;

class Reports
{
    
    private static function getPurchasedProduct($date, $con) 
    {
        $purchasedProducts = PurchaseDetailQuery::create()
            ->filterByStatus('Active')
            ->useStockQuery()
                ->leftJoin('Product')
                ->leftJoin('Unit')
                ->withColumn('Product.Name', 'product_name')
                ->withColumn('Unit.Name', 'unit_name')
            ->endUse()
            ->usePurchaseQuery()
                ->filterByStatus('Active')
                ->filterByDate(array('min' => $date->start, 'max' => $date->until))
            ->endUse()
            ->withColumn('SUM(PurchaseDetail.Amount)', 'purchased_amount')
            ->withColumn('SUM(PurchaseDetail.TotalPrice)', 'purchased_total')
            ->select(array(
                'stock_id',
                'product_name',
                'unit_name',
                'purchased_amount',
                'purchased_total'
            ))
            ->groupBy('PurchaseDetail.StockId')
            ->orderBy('purchased_amount', 'DESC')
            ->find($con);
        
        $data = [];
        foreach($purchasedProducts as $purchasedProduct) {
            $data[] = $purchasedProduct;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    } 
    
    private static function getSaledProduct($date, $con) 
    {
        $saledProducts = SalesDetailQuery::create()
            ->filterByStatus('Active')
            ->useStockQuery()
                ->leftJoin('Product')
                ->leftJoin('Unit')
                ->withColumn('Product.Name', 'product_name')
                ->withColumn('Unit.Name', 'unit_name')
            ->endUse()
            ->useSalesQuery()
                ->filterByStatus('Active')
                ->filterByDate(array('min' => $date->start, 'max' => $date->until))
            ->endUse()
            ->withColumn('SUM(SalesDetail.Amount)', 'saled_amount')
            ->withColumn('SUM(SalesDetail.TotalPrice)', 'saled_total')
            ->select(array(
                'stock_id',
                'product_name',
                'unit_name',
                'saled_amount',
                'saled_total'
            ))
            ->groupBy('SalesDetail.StockId')
            ->orderBy('saled_amount', 'DESC')
            ->find($con);
        
        $data = [];
        foreach($saledProducts as $saledProduct) {
            $data[] = $saledProduct;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    } 
    
    private static function getStats($date, $con) 
    {
        $sales = SalesQuery::create()
            ->filterByStatus('Active')
            ->filterByDate(array('min' => $date->start, 'max' => $date->until))
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
            ->filterByDate(array('min' => $date->start, 'max' => $date->until))
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

    public static function custom($params, $currentUser, $con)
    {
        if (!isset($params->start) || !isset($params->until)) throw new \Exception('Missing parameter');
        
        $date = new \stdClass();
        $date->start = new \DateTime($params->start);
        $date->until = new \DateTime($params->until);
        
        $results = Reports::getStats($date, $con);
        
        return $results;
    }

    public static function customPurchasedProduct($params, $currentUser, $con) 
    {
        if (!isset($params->start) || !isset($params->until)) throw new \Exception('Missing parameter');
        
        $date = new \stdClass();
        $date->start = new \DateTime($params->start);
        $date->until = new \DateTime($params->until);
        
        $results = Reports::getPurchasedProduct($date, $con);
        
        return $results;
    }

    public static function customSaledProduct($params, $currentUser, $con) 
    {
        if (!isset($params->start) || !isset($params->until)) throw new \Exception('Missing parameter');
        
        $date = new \stdClass();
        $date->start = new \DateTime($params->start);
        $date->until = new \DateTime($params->until);
        
        $results = Reports::getSaledProduct($date, $con);
        
        return $results;
    }

    public static function monthly($params, $currentUser, $con)
    {
        if (!isset($params->month)) throw new \Exception('Missing parameter');
        
        $picked = new \DateTime($params->month);
        
        $date = new \stdClass();
        $date->start = new \DateTime($picked->format('Y-m-01'));
        $date->until = new \DateTime($picked->format('Y-m-t'));
        
        $results = Reports::getStats($date, $con);
        
        return $results;
    }

    public static function monthlyPurchasedProduct($params, $currentUser, $con) 
    {
        if (!isset($params->month)) throw new \Exception('Missing parameter');
        
        $picked = new \DateTime($params->month);
        
        $date = new \stdClass();
        $date->start = new \DateTime($picked->format('Y-m-01'));
        $date->until = new \DateTime($picked->format('Y-m-t'));
        
        $results = Reports::getPurchasedProduct($date, $con);
        
        return $results;
    }

    public static function monthlySaledProduct($params, $currentUser, $con) 
    {
        if (!isset($params->month)) throw new \Exception('Missing parameter');
        
        $picked = new \DateTime($params->month);
        
        $date = new \stdClass();
        $date->start = new \DateTime($picked->format('Y-m-01'));
        $date->until = new \DateTime($picked->format('Y-m-t'));
        
        $results = Reports::getSaledProduct($date, $con);
        
        return $results;
    }

}