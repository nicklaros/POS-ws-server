<?php

namespace App;

use ORM\UserQuery;
use ORM\UserDetailQuery;

class Options
{

    public static function changePassword($params, $currentUser, $con)
    {
        if (
            !isset($params->oldPassEncrypted) 
            || 
            !isset($params->newPassEncrypted)
        ){
            throw new \Exception('Missing parameter');
        }
        
        $user = UserQuery::create()
            ->filterById($currentUser->id)
            ->filterByPassword($params->oldPassEncrypted)
            ->findOne($con);

        if(!$user) throw new \Exception('Password lama salah!');

        $user
            ->setPassword($params->newPassEncrypted)
            ->save($con);

        $results['success'] = true;
        $results['data'] = 'Yay';
        
        return $results;
    }

    public static function loadBiodata($params, $currentUser, $con)
    {
        $userDetail = UserDetailQuery::create()
            ->filterById($currentUser->id)
            ->select(array(
                'name',
                'address',
                'phone'
            ))
            ->findOne($con);

        if(!$userDetail) throw new \Exception('Anda tidak terdaftar sebagai user!');

        $results['success'] = true;
        $results['data'] = $userDetail;
        
        return $results;
    }

    public static function updateBiodata($params, $currentUser, $con)
    {
        $userDetail = UserDetailQuery::create()
            ->filterById($currentUser->id)
            ->findOne($con);

        if(!$userDetail) throw new \Exception('Anda tidak terdaftar sebagai user!');

        $userDetail
            ->setName($params->name)
            ->setAddress($params->address)
            ->setPhone($params->phone)
            ->save($con);

        $results['success'] = true;
        $results['data'] = 'Yay';
        
        return $results;
    }

}