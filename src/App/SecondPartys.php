<?php

namespace App;

use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\SecondParty;
use ORM\SecondPartyQuery;

class SecondPartys
{
    
    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_second_party')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // create new record
        $secondParty = new SecondParty();
        $secondParty
            ->setRegisteredDate(Date('Y-m-d'))
            ->setName($params->name)
            ->setAddress($params->address)
            ->setGender($params->gender)
            ->setPhone($params->phone)
            ->setType($params->type)
            ->setStatus('Active')
            ->save($con);

        // log history
        $rowHistory = new RowHistory();
        $rowHistory->setRowId($secondParty->getId())
            ->setData('second_party')
            ->setTime(time())
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);
        
        $params->id = $secondParty->getId();

        $results['success'] = true;
        $results['data'] = $params;

        return $results;
    }

}