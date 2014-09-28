<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\Unit;
use ORM\UnitQuery;

class Units
{

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_unit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check whether unit is already exist
        $unit = UnitQuery::create()->filterByStatus('Active')->filterByName($params->name)->count($con);
        if ($unit != 0) throw new \Exception('Satuan ' . $params->name . ' sudah ada dalam data');

        // create new record
        $unit = new Unit();
        $unit
            ->setName($params->name)
            ->setStatus('Active')
            ->save($con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($unit->getId())
            ->setData('unit')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);
        
        $params->id = $unit->getId();

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_unit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $units = UnitQuery::create()->filterById($params->id)->find($con);
        if (!$units) throw new \Exception('Data tidak ditemukan');

        foreach($units as $unit)
        {
            $unit
                ->setStatus('Deleted')
                ->save($con);

            $rowHistory = new RowHistory();
            $rowHistory->setRowId($unit->getId())
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
        $permission = RolePermissionQuery::create()->select('update_unit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $unit = UnitQuery::create()
            ->filterByStatus('Active')
            ->select(array(
                'id',
                'name'
            ))
            ->findOneById($params->id);

        if (!$unit) throw new \Exception('Data tidak ditemukan');

        $results['success'] = true;
        $results['data'] = $unit;

        return $results;
    }

    public static function read($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_unit')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $units = UnitQuery::create()
            ->filterByStatus('Active');

        if(isset($params->name)) $units->filterByName('%' . $params->name . '%');

        $units = $units
            ->select(array(
                'id',
                'name'
            ));

        foreach($params->sort as $sorter){
            $units->orderBy($sorter->property, $sorter->direction);
        }

        $units = $units->paginate($page, $limit);

        $total = $units->getNbResults();
        
        $data = [];
        foreach($units as $unit) {
            $data[] = $unit;
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

        // check whether unit is already exist
        $unit = UnitQuery::create()
            ->filterByStatus('Active')
            ->filterByName($params->name)
            ->where('Unit.Id not like ?', $params->id)
            ->count($con);
                    
        if ($unit != 0) throw new \Exception('Satuan ' . $params->name . ' sudah ada dalam data');

        $unit = UnitQuery::create()->filterByStatus('Active')->findOneById($params->id, $con);
        if(!$unit) throw new \Exception('Data tidak ditemukan');

        $unit
            ->setName($params->name)
            ->save($con);

        $rowHistory = new RowHistory();
        $rowHistory->setRowId($params->id)
            ->setData('unit')
            ->setTime(time())
            ->setOperation('update')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

}