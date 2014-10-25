<?php

namespace App;

use ORM\Role;
use ORM\RoleQuery;
use ORM\RolePermission;
use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\UserQuery;

class Roles
{

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_role')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check whether role is already exist
        $role = RoleQuery::create()->filterByStatus('Active')->filterByName($params->name)->count($con);
        if ($role != 0) throw new \Exception('Jabatan ' . $params->name . ' sudah ada dalam data');

        // create new role
        $role = new Role();
        $role
            ->setName($params->name)
            ->save($con);

        // create new role permission with default value
        $rolePermission = new RolePermission();
        $rolePermission
            ->setId($role->getId())
            ->save($con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($role->getId())
            ->setData('role')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);
        
        $params->id = $role->getId();

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_role')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');
        
        if ( in_array(1, $params->id) ) throw new Exception('Jabatan Super User tidak bisa dihapus.');

        $roles = RoleQuery::create()->filterById($params->id)->find($con);
        if (!$roles) throw new \Exception('Data tidak ditemukan');

        foreach($roles as $role)
        {
            // check users currently assigned to this role
            $users = UserQuery::create()->filterByRoleId($role->getId())->find($con);
            
            // if any user found, then update their role to NULL
            foreach($users as $user)
            {
                $user
                    ->setRoleId(null)
                    ->save($con);
            }

            $role
                ->setStatus('Deleted')
                ->save($con);

            $rowHistory = new RowHistory();
            $rowHistory->setRowId($role->getId())
                ->setData('unit')
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
        $permission = RolePermissionQuery::create()->select('update_role')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $role = RoleQuery::create()
            ->filterByStatus('Active')
            ->select(array(
                'id',
                'name'
            ))
            ->findOneById($params->id);

        if (!$role) throw new \Exception('Data tidak ditemukan');

        $results['success'] = true;
        $results['data'] = $role;

        return $results;
    }

    public static function loadPermission($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_role')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $rolePermission = RolePermissionQuery::create()
            ->select(array(
                'id',
                'create_product',
                'read_product',
                'update_product',
                'destroy_product',
                'create_purchase',
                'read_purchase',
                'update_purchase',
                'destroy_purchase',
                'create_role',
                'read_role',
                'update_role',
                'destroy_role',
                'create_sales',
                'read_sales',
                'update_sales',
                'destroy_sales',
                'create_second_party',
                'read_second_party',
                'update_second_party',
                'destroy_second_party',
                'create_stock',
                'read_stock',
                'update_stock',
                'destroy_stock',
                'create_unit',
                'read_unit',
                'update_unit',
                'destroy_unit',
                'create_user',
                'read_user',
                'update_user',
                'destroy_user',
                'reset_pass_user',
                'read_credit',
                'pay_credit',
                'read_debit',
                'pay_debit'
            ))
            ->findOneById($params->id);

        if (!$rolePermission) throw new \Exception('Data tidak ditemukan');

        $results['success'] = true;
        $results['data'] = $rolePermission;

        return $results;
    }

    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_role')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $roles = RoleQuery::create()
            ->filterByStatus('Active')
            ->where('Role.Id not like 1');

        if(isset($params->name)) $roles->filterByName('%' . $params->name . '%');

        $roles = $roles
            ->select(array(
                'id',
                'name'
            ));

        foreach($params->sort as $sorter){
            $roles->orderBy($sorter->property, $sorter->direction);
        }

        $roles = $roles->paginate($page, $limit);

        $total = $roles->getNbResults();
        
        $data = [];
        foreach($roles as $role) {
            $data[] = $role;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function savePermission($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_role')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $rolePermission = RolePermissionQuery::create()->findOneById($params->id, $con);
        if(!$rolePermission) throw new \Exception('Data tidak ditemukan');

        $rolePermission
            ->setCreateProduct($params->create_product)
            ->setReadProduct($params->read_product)
            ->setUpdateProduct($params->update_product)
            ->setDestroyProduct($params->destroy_product)
            ->setCreatePurchase($params->create_purchase)
            ->setReadPurchase($params->read_purchase)
            ->setUpdatePurchase($params->update_purchase)
            ->setDestroyPurchase($params->destroy_purchase)
            ->setCreateRole($params->create_role)
            ->setReadRole($params->read_role)
            ->setUpdateRole($params->update_role)
            ->setDestroyRole($params->destroy_role)
            ->setCreateSales($params->create_sales)
            ->setReadSales($params->read_sales)
            ->setUpdateSales($params->update_sales)
            ->setDestroySales($params->destroy_sales)
            ->setCreateSecondParty($params->create_second_party)
            ->setReadSecondParty($params->read_second_party)
            ->setUpdateSecondParty($params->update_second_party)
            ->setDestroySecondParty($params->destroy_second_party)
            ->setCreateStock($params->create_stock)
            ->setReadStock($params->read_stock)
            ->setUpdateStock($params->update_stock)
            ->setDestroyStock($params->destroy_stock)
            ->setCreateUnit($params->create_unit)
            ->setReadUnit($params->read_unit)
            ->setUpdateUnit($params->update_unit)
            ->setDestroyUnit($params->destroy_unit)
            ->setCreateUser($params->create_user)
            ->setReadUser($params->read_user)
            ->setUpdateUser($params->update_user)
            ->setDestroyUser($params->destroy_user)
            ->setResetPassUser($params->reset_pass_user)
            ->setReadCredit($params->read_credit)
            ->setPayCredit($params->pay_credit)
            ->setReadDebit($params->read_debit)
            ->setPayDebit($params->pay_debit)
            ->save($con);

        $rowHistory = new RowHistory();
        $rowHistory->setRowId($params->id)
            ->setData('role_permission')
            ->setTime(time())
            ->setOperation('update')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_role')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check whether role is already exist
        $role = RoleQuery::create()
            ->filterByStatus('Active')
            ->filterByName($params->name)
            ->where('Role.Id not like ?', $params->id)
            ->count($con);
                    
        if ($role != 0) throw new \Exception('Jabatan ' . $params->name . ' sudah ada dalam data');

        $role = RoleQuery::create()->filterByStatus('Active')->findOneById($params->id, $con);
        if(!$role) throw new \Exception('Data tidak ditemukan');

        $role
            ->setName($params->name)
            ->save($con);

        $rowHistory = new RowHistory();
        $rowHistory->setRowId($params->id)
            ->setData('role')
            ->setTime(time())
            ->setOperation('update')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

}