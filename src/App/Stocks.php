<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\ProductQuery;
use ORM\Stock;
use ORM\StockQuery;

class Stocks
{

    private static function seeker($params, $currentUser, $con)
    {
        $stock = StockQuery::create()
            ->leftJoin('Product')
            ->leftJoin('Unit')
            ->filterByStatus('Active')
            ->select(array(
                'id',
                'product_id',
                'amount',
                'unit_id',
                'buy',
                'sell_public',
                'sell_distributor',
                'sell_misc',
                'discount',
                'unlimited'
            ))
            ->withColumn('Product.Name', 'product_name')
            ->withColumn('Unit.Name', 'unit_name')
            ->findOneById($params->id);

        if (!$stock) throw new \Exception('Data tidak ditemukan');

        $results['data'] = $stock;

        return $results;
    }

    public static function addVariant($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_stock')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check whether chosen product is still Active
        $product = ProductQuery::create()->select('status')->findOneById($params->product_id, $con);
        if (!$product || $product != 'Active') throw new \Exception('Produk tidak ditemukan. Mungkin Produk itu sudah dihapus.');
        
        // make sure there are no duplicate (same product and unit) variant in stock
        $stock = StockQuery::create()
            ->leftJoin('Product')
            ->leftJoin('Unit')
            ->filterByProductId($params->product_id)
            ->filterByUnitId($params->unit_id)
            ->withColumn('Product.Name', 'product_name')
            ->withColumn('Unit.Name', 'unit_name')
            ->select(array(
                'product_name',
                'unit_name',
            ))
            ->findOne($con);
        if ($stock) throw new \Exception('Gagal menyimpan karena variant ' . $stock['product_name'] . ' <strong>' . $stock['unit_name'] . '</strong> sudah ada.');
        
        // create new stock
        $stock = new Stock();
        $stock
            ->setProductId($params->product_id)
            ->setUnitId($params->unit_id)
            ->setUnlimited(isset($params->unlimited) ? $params->unlimited : 0)
            ->setStatus('Active')
            ->save($con);

        $params->id = $stock->getId();
        
        $stock = Stocks::seeker($params, $currentUser, $con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($params->id)
            ->setData('stock')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);
        
        $results['success'] = true;
        $results['data'] = $stock['data'];

        return $results;
    }

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_stock')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check whether chosen product is still Active
        $product = ProductQuery::create()->select('status')->findOneById($params->product_id, $con);
        if (!$product || $product != 'Active') throw new \Exception('Produk tidak ditemukan. Mungkin Produk itu sudah dihapus.');
        
        // make sure there are no duplicate (same product and unit) variant in stock
        $stock = StockQuery::create()
            ->leftJoin('Product')
            ->leftJoin('Unit')
            ->filterByProductId($params->product_id)
            ->filterByUnitId($params->unit_id)
            ->withColumn('Product.Name', 'product_name')
            ->withColumn('Unit.Name', 'unit_name')
            ->select(array(
                'product_name',
                'unit_name',
            ))
            ->findOne($con);
        if ($stock) throw new \Exception('Gagal menyimpan karena variant ' . $stock['product_name'] . ' <strong>' . $stock['unit_name'] . '</strong> sudah ada.');
        
        // create new stock
        $stock = new Stock();
        $stock
            ->setProductId($params->product_id)
            ->setUnitId($params->unit_id)
            ->setAmount(isset($params->amount) ? $params->amount : 0)
            ->setBuy($params->buy)
            ->setSellPublic($params->sell_public)
            ->setSellDistributor($params->sell_distributor)
            ->setSellMisc($params->sell_misc)
            ->setDiscount($params->discount)
            ->setUnlimited(isset($params->unlimited) ? $params->unlimited : 0)
            ->setStatus('Active')
            ->save($con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($stock->getId())
            ->setData('stock')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['id'] = $stock->getId();

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_stock')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $stocks = StockQuery::create()
            ->filterById($params->id)
            ->find($con);

        if (!$stocks) throw new \Exception('Data tidak ditemukan');

        foreach($stocks as $stock)
        {
            $stock->setStatus('Deleted')->save($con);

            $rowHistory = new RowHistory();
            $rowHistory->setRowId($stock->getId())
                ->setData('stock')
                ->setTime(time())
                ->setOperation("destroy")
                ->setUserId($currentUser->id)
                ->save($con);
        }

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

    public static function getOne($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_stock')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $stock = Stocks::seeker($params, $currentUser, $con);
        
        $results['success'] = true;
        $results['data'] = $stock['data'];
        
        return $results;
    }

    public static function loadFormEdit($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_stock')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $stock = Stocks::seeker($params, $currentUser, $con);
        
        $results['success'] = true;
        $results['data'] = $stock['data'];
        
        return $results;
    }

    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_stock')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $stock = StockQuery::create()
            ->filterByStatus('Active');
            
        $stock->useProductQuery()->filterByStatus('Active')->endUse();

        if(isset($params->code)) $stock->useProductQuery()->filterByCode("%$params->code%")->endUse();
        if(isset($params->product)) $stock->useProductQuery()->filterByName("%$params->product%")->endUse();

        $stock = $stock
            ->select(array(
                'id',
                'product_id',
                'amount',
                'unit_id',
                'buy',
                'sell_public',
                'sell_distributor',
                'sell_misc',
                'discount',
                'unlimited'
            ))
            ->leftJoin('Product')
            ->withColumn('Product.Code', 'code')
            ->withColumn('Product.Name', 'product')
            ->leftJoin('Unit')
            ->withColumn('Unit.Name', 'unit');

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
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_stock')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check whether chosen product is still Active
        $product = ProductQuery::create()->select('status')->findOneById($params->product_id, $con);
        if (!$product || $product != 'Active') throw new \Exception('Produk tidak ditemukan. Mungkin Produk itu sudah dihapus.');
        
        $stock = StockQuery::create()->findOneById($params->id, $con);
        if(!$stock) throw new \Exception('Data tidak ditemukan');

        $stock
            ->setProductId($params->product_id)
            ->setAmount($params->amount)
            ->setUnitId($params->unit_id)
            ->setBuy($params->buy)
            ->setSellPublic($params->sell_public)
            ->setSellDistributor($params->sell_distributor)
            ->setSellMisc($params->sell_misc)
            ->setDiscount($params->discount)
            ->setUnlimited(isset($params->unlimited) ? $params->unlimited : 0)
            ->save($con);

        $rowHistory = new RowHistory();
        $rowHistory->setRowId($params->id)
            ->setData('stock')
            ->setTime(time())
            ->setOperation('update')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

}