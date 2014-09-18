<?php

namespace App;

use ORM\Customer;
use ORM\CustomerQuery;
use ORM\RolePermissionQuery;
use ORM\RowHistory;

class Customers
{

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_customer')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // create new record
        $customer = new Customer();
        $customer
            ->setRegisteredDate($params->registered_date)
            ->setName($params->name)
            ->setAddress($params->address)
            ->setBirthday($params->birthday)
            ->setGender($params->gender)
            ->setPhone($params->phone)
            ->setStatus('Active')
            ->save($con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($customer->getId())
            ->setData('customer')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);
        
        $params->id = $customer->getId();

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_customer')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $customers = CustomerQuery::create()->filterById($params->id)->find($con);
        if (!$customers) throw new \Exception('Data tidak ditemukan');

        foreach($customers as $customer)
        {
            $customer
                ->setStatus('Deleted')
                ->save($con);

            $rowHistory = new RowHistory();
            $rowHistory->setRowId($customer->getId())
                ->setData('customer')
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
        $permission = RolePermissionQuery::create()->select('update_customer')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $customer = CustomerQuery::create()
            ->filterByStatus('Active')
            ->select(array(
                'id',
                'registered_date',
                'name',
                'address',
                'birthday',
                'gender',
                'phone'
            ))
            ->findOneById($params->id);

        if (!$customer) throw new \Exception('Data tidak ditemukan');

        $results['success'] = true;
        $results['data'] = $customer;

        return $results;
    }

    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_customer')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $customers = CustomerQuery::create()
            ->filterByStatus('Active')
            ->where('Customer.Id not like ?', 0);

        if(isset($params->name)) $customers->filterByName('%' . $params->name . '%');

        $customers = $customers
            ->select(array(
                'id',
                'registered_date',
                'name',
                'address',
                'birthday',
                'gender',
                'phone'
            ));

        foreach($params->sort as $sorter){
            $customers->orderBy($sorter->property, $sorter->direction);
        }

        $customers = $customers->paginate($page, $limit);

        $total = $customers->getNbResults();
        
        $data = [];
        foreach($customers as $customer) {
            $data[] = $customer;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_unit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $customer = CustomerQuery::create()->filterByStatus('Active')->findOneById($params->id, $con);
        if(!$customer) throw new \Exception('Data tidak ditemukan');

        $customer
            ->setRegisteredDate($params->registered_date)
            ->setName($params->name)
            ->setAddress($params->address)
            ->setBirthday($params->birthday)
            ->setGender($params->gender)
            ->setPhone($params->phone)
            ->save($con);

        $rowHistory = new RowHistory();
        $rowHistory->setRowId($params->id)
            ->setData('customer')
            ->setTime(time())
            ->setOperation('update')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

}