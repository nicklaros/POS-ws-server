<?php

namespace App;

use ORM\OptionQuery;
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

    public static function loadAppPhoto($params, $currentUser, $con)
    {
        $data = [];
        $option = OptionQuery::create()
            ->filterByName([
                'app_photo'
            ])
            ->findOne($con);

        $data[$option->getName()] = $option->getValue();

        $results['success'] = true;
        $results['data'] = $data;
        
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

    public static function loadClientIdentity($params, $currentUser, $con)
    {
        $data = [];
        $options = OptionQuery::create()
            ->filterByName([
                'client_name',
                'client_address',
                'client_phone',
                'client_email',
                'client_website'
            ])
            ->find($con);

        foreach($options as $row){
            $data[$row->getName()] = $row->getValue();
        }

        $results['success'] = true;
        $results['data'] = $data;
        
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

    public static function updateClientIdentity($params, $currentUser, $con)
    {
        $option = OptionQuery::create()
            ->filterByName('client_name')
            ->findOne($con);
        $option
            ->setValue($params->client_name)
            ->save($con);

        $option = OptionQuery::create()
            ->filterByName('client_address')
            ->findOne($con);
        $option
            ->setValue($params->client_address)
            ->save($con);

        $option = OptionQuery::create()
            ->filterByName('client_phone')
            ->findOne($con);
        $option
            ->setValue($params->client_phone)
            ->save($con);

        $option = OptionQuery::create()
            ->filterByName('client_email')
            ->findOne($con);
        $option
            ->setValue($params->client_email)
            ->save($con);

        $option = OptionQuery::create()
            ->filterByName('client_website')
            ->findOne($con);
        $option
            ->setValue($params->client_website)
            ->save($con);

        $results['success'] = true;
        $results['data'] = 'Yay';
        
        return $results;
    }

}