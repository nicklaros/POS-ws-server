<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\Product;
use ORM\ProductQuery;

class Products
{

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_product')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check whether picked code is already used
        $product = ProductQuery::create()->filterByCode($params->code)->count($con);
        if ($product != 0) throw new \Exception('Kode produk sudah terpakai. Pilih kode lainnya.');

        // create new record
        $product = new Product();
        $product
            ->setCode($params->code)
            ->setName($params->name)
            ->setStatus('Active')
            ->save($con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($product->getId())
            ->setData('product')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);
        
        $params->id = $product->getId();

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_product')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $products = ProductQuery::create()
            ->filterById($params->id)
            ->find($con);

        if (!$products) throw new \Exception('Data tidak ditemukan');

        foreach($products as $product)
        {
            $product
                ->setCode('')
                ->setStatus('Deleted')
                ->save($con);

            $rowHistory = new RowHistory();
            $rowHistory->setRowId($product->getId())
                ->setData('product')
                ->setTime(time())
                ->setOperation('destroy')
                ->setUserId($currentUser->id)
                ->save($con);
        }

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

    public static function loadFormEdit($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_product')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $product = ProductQuery::create()
            ->filterByStatus('Active')
            ->select(array(
                'id',
                'code',
                'name'
            ))
            ->findOneById($params->id);

        if (!$product) throw new \Exception('Data tidak ditemukan');

        $results['success'] = true;
        $results['data'] = $product;

        return $results;
    }

    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_product')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $products = ProductQuery::create()
            ->filterByStatus('Active');

        if(isset($params->code_or_name)) {
            $products
                ->condition('cond1', 'Product.Name like ?', '%' . $params->code_or_name . '%')
                ->condition('cond2', 'Product.Code like ?', '%' . $params->code_or_name . '%')
                ->where(array('cond1', 'cond2'), 'or');
        }
        
        $products = $products
            ->select(array(
                'id',
                'code',
                'name'
            ));

        foreach($params->sort as $sorter){
            $products->orderBy($sorter->property, $sorter->direction);
        }

        $products = $products->paginate($page, $limit);

        $total = $products->getNbResults();
        
        $data = [];
        foreach($products as $product) {
            $data[] = $product;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_product')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check whether picked code is already used
        $product = ProductQuery::create()
            ->filterByCode($params->code)
            ->where("Product.Id not like ?", $params->id)
            ->count($con);
        
        if ($product != 0) throw new \Exception('Kode produk sudah terpakai. Pilih kode lainnya.');

        $product = ProductQuery::create()->findOneById($params->id, $con);
        if(!$product) throw new \Exception('Data tidak ditemukan');

        $product
            ->setCode($params->code)
            ->setName($params->name)
            ->save($con);

        $rowHistory = new RowHistory();
        $rowHistory->setRowId($params->id)
            ->setData('product')
            ->setTime(time())
            ->setOperation('update')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

}