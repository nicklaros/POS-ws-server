<?php

namespace App;

use ORM\Product;
use ORM\ProductQuery;

class Combos
{

    public static function product($params, $currentUser, $con)
    {
        $products = ProductQuery::create()
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

}