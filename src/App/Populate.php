<?php

namespace App;

use ORM\StockQuery;

class Populate
{

    public static function stock($params, $currentUser, $con)
    {
        $stocks = StockQuery::create()
            ->filterByStatus('Active')
            ->leftJoin('Product')
            ->leftJoin('Unit');

        if(isset($params->product_id)) $stocks->filterByProductId($params->product_id);
        
        // if limit is not specified then make it 0
        $limit = (isset($params->limit) ? $params->limit : 0);
        
        $stocks = $stocks
            ->select(array(
                'unit_id',
                'amount',
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
            ->limit($limit)
            ->find($con);

        $data = [];
        foreach($stocks as $stock) {
            $data[] = $stock;
        }
        $results['success'] = true;
        $results['data'] = $data;
        
        return $results;
    }

}