<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\Sales;
use ORM\SalesQuery;

class Sale
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
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_sales')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $sales = SalesQuery::create()
            ->filterByStatus('Active');
            
        if(isset($params->code)) $sales->useProductQuery()->filterByCode("%$params->code%")->endUse();
        if(isset($params->product)) $sales->useProductQuery()->filterByName("%$params->product%")->endUse();

        $sales = $sales
            ->select(array(
                'id',
                'date',
                'customer_id',
                'buy_price',
                'total_price',
                'paid',
                'cashier_id',
                'note'
            ))
            ->leftJoin('Customer')
            ->withColumn('Customer.Name', 'customer')
            ->leftJoin('Cashier')
            ->withColumn('Cashier.Name', 'cashier');

        foreach($params->sort as $sorter){
            $sales->orderBy($sorter->property, $sorter->direction);
        }

        $sales = $sales->paginate($page, $limit);

        $total = $sales->getNbResults();
        
        $data = [];
        foreach($sales as $row) {
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