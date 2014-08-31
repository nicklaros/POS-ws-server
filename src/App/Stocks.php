<?php

namespace App;

use ORM\Product;
use ORM\ProductQuery;
use ORM\Role;
use ORM\RoleQuery;
use ORM\RolePermission;
use ORM\RolePermissionQuery;
use ORM\Stock;
use ORM\StockQuery;
use ORM\User;
use ORM\UserQuery;

class Stocks
{

    public static function create($params, $currentUser, $con)
    {
    
    }

    public static function destroy($params, $currentUser, $con)
    {
    
    }

    public static function loadFormEdit($params, $currentUser, $con)
    {
    
    }

    public static function read($params, $currentUser, $con)
    {
        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $stock = StockQuery::create();

        if(isset($params->code)) $stock->useProductQuery()->filterByCode("%$params->code%")->endUse();
        if(isset($params->name)) $stock->useProductQuery()->filterByName("%$params->name%")->endUse();

        $stock = $stock
            ->select(array(
                'id',
                'amount',
                'unit',
                'buy',
                'sell_public',
                'sell_distributor',
                'sell_misc',
                'discount',
            ))
            ->leftJoin('Product')
            ->withColumn('Product.Code', 'code')
            ->withColumn('Product.Name', 'name');

        foreach($params->sort as $sorter){
            $stock->orderBy($sorter->property, $sorter->direction);
        }

        $stock = $stock->paginate($page, $limit);

        $total = $stock->getNbResults();
        
        $data = [];
        foreach($stock as $row) {
            $data[] = $row;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
    
    }

}