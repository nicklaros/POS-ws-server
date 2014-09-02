<?php

namespace App;

use ORM\ProductQuery;
use ORM\UnitQuery;

class Combos
{

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