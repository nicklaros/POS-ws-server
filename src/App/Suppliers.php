<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\SecondParty;
use ORM\SecondPartyQuery;

class Suppliers
{

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_second_party')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // create new record
        $supplier = new SecondParty();
        $supplier
            ->setRegisteredDate(Date('Y-m-d'))
            ->setName($params->name)
            ->setAddress($params->address)
            ->setPhone($params->phone)
            ->setType('Supplier')
            ->setStatus('Active')
            ->save($con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($supplier->getId())
            ->setData('supplier')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);
        
        $params->id = $supplier->getId();

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_second_party')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $suppliers = SecondPartyQuery::create()->filterByStatus('Active')->filterById($params->id)->find($con);
        if (!$suppliers) throw new \Exception('Data tidak ditemukan');

        foreach($suppliers as $supplier)
        {
            $supplier
                ->setStatus('Deleted')
                ->save($con);

            $rowHistory = new RowHistory();
            $rowHistory->setRowId($supplier->getId())
                ->setData('supplier')
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
        $permission = RolePermissionQuery::create()->select('update_second_party')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $supplier = SecondPartyQuery::create()
            ->filterByStatus('Active')
            ->select(array(
                'id',
                'name',
                'address',
                'phone'
            ))
            ->findOneById($params->id);

        if (!$supplier) throw new \Exception('Data tidak ditemukan');

        $results['success'] = true;
        $results['data'] = $supplier;

        return $results;
    }

    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_second_party')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $suppliers = SecondPartyQuery::create()
            ->filterByStatus('Active')
            ->filterByType('Supplier')
            ->where('SecondParty.Id not like ?', 0);

        if(isset($params->name)) $suppliers->filterByName('%' . $params->name . '%');

        $suppliers = $suppliers
            ->select(array(
                'id',
                'name',
                'address',
                'phone'
            ));

        foreach($params->sort as $sorter){
            $suppliers->orderBy($sorter->property, $sorter->direction);
        }

        $suppliers = $suppliers->paginate($page, $limit);

        $total = $suppliers->getNbResults();
        
        $data = [];
        foreach($suppliers as $supplier) {
            $data[] = $supplier;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_second_party')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $supplier = SecondPartyQuery::create()->filterByStatus('Active')->findOneById($params->id, $con);
        if(!$supplier) throw new \Exception('Data tidak ditemukan');

        $supplier
            ->setName($params->name)
            ->setAddress($params->address)
            ->setPhone($params->phone)
            ->save($con);

        $rowHistory = new RowHistory();
        $rowHistory->setRowId($params->id)
            ->setData('supplier')
            ->setTime(time())
            ->setOperation('update')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

}