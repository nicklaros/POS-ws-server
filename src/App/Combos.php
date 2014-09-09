<?php

namespace App;

use ORM\CustomerQuery;
use ORM\ProductQuery;
use ORM\StockQuery;
use ORM\UnitQuery;
use ORM\UserDetailQuery;

class Combos
{

    public static function cashier($params, $currentUser, $con)
    {
        $cashiers = UserDetailQuery::create()
            ->useUserQuery()
                ->filterByStatus('Active')
            ->endUse()
            ->orderBy('name', 'ASC');

        if(isset($params->query)) $cashiers->where('UserDetail.Name like ?', "%$params->query%");
        
        $cashiers = $cashiers
            ->select(array(
                'id',
                'name'
            ))
            ->limit(20)
            ->find($con);

        $data = [];
        foreach($cashiers as $cashier) {
            $data[] = $cashier;
        }
        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

    public static function customer($params, $currentUser, $con)
    {
        $customers = CustomerQuery::create()
            ->filterByStatus('Active')
            ->orderBy('name', 'ASC');

        if(isset($params->query)) $customers->where('Customer.Name like ?', "%$params->query%");
        
        $customers = $customers
            ->select(array(
                'id',
                'name'
            ))
            ->limit(20)
            ->find($con);

        $data = [];
        foreach($customers as $customer) {
            $data[] = $customer;
        }
        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

    public static function product($params, $currentUser, $con)
    {
        $products = ProductQuery::create()
            ->filterByStatus('Active')
            ->orderBy('name', 'ASC');

        if(isset($params->query)){
            $products->condition('cond1', 'Product.Name like ?', "%$params->query%");
            $products->condition('cond2', 'Product.Code like ?', "%$params->query%");
            $products->where(array('cond1', 'cond2'), 'or');
        }
        
        $products = $products
            ->select(array(
                'id',
                'code',
                'name'
            ))
            ->limit(20)
            ->find($con);

        $data = [];
        foreach($products as $product) {
            $data[] = $product;
        }
        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }
    
    public static function stock($params, $currentUser, $con)
    {
        $stocks = StockQuery::create()
            ->filterByStatus('Active')
            ->leftJoin('Product')
            ->leftJoin('Unit');

        if(isset($params->query)){
            $stocks->condition('cond1', 'Product.Name like ?', "%$params->query%");
            $stocks->condition('cond2', 'Product.Code like ?', "%$params->query%");
            $stocks->where(array('cond1', 'cond2'), 'or');
        }
        
        $stocks = $stocks
            ->select(array(
                'unit_id',
                'buy',
                'sell_public',
                'sell_distributor',
                'sell_misc',
                'discount',
            ))
            ->withColumn('Stock.Id', 'stock_id')
            ->withColumn('Product.Code', 'product_code')
            ->withColumn('Product.Name', 'product_name')
            ->withColumn('Unit.Name', 'unit_name')
            ->orderBy('product_name', 'ASC')
            ->limit(20)
            ->find($con);

        $data = [];
        foreach($stocks as $stock) {
            $data[] = $stock;
        }
        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

    public static function unit($params, $currentUser, $con)
    {
        $units = UnitQuery::create()
            ->orderBy('name', 'ASC');

        if (isset($params->query)) $units->where('Unit.Name like ?', "%$params->query%");
        
        $units = $units
            ->select(array(
                'id',
                'name'
            ))
            ->limit(20)
            ->find($con);

        $data = [];
        foreach($units as $unit) {
            $data[] = $unit;
        }
        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

}