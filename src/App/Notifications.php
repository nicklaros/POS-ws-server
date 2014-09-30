<?php

namespace App;

use ORM\NotificationQuery;
use ORM\NotificationOnUserQuery;
use ORM\NotificationOption;
use ORM\NotificationOptionQuery;
use ORM\RolePermissionQuery;
use ORM\StockQuery;

class Notifications
{

    public static function addOptionPrice($params, $currentUser, $con)
    {
        $option = NotificationOptionQuery::create()
            ->filterByType('price')
            ->filterByRoleId($params->role)
            ->count($con);

        if ($option != 0) throw new \Exception('Jabatan sudah dimasukkan');
        
        $option = new NotificationOption();
        $option
            ->setType('price')
            ->setRoleId($params->role)
            ->save($con);

        $results['success'] = true;
        $results['data'] = 'yay';

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
        $notifications = NotificationOnUserQuery::create()
            ->filterById($params->id)
            ->find($con);

        if (!$notifications) throw new \Exception('Data tidak ditemukan');

        foreach($notifications as $notification)
        {
            $notification
                ->setStatus('Deleted')
                ->save($con);
        }

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

    public static function read($params, $currentUser, $con)
    {
        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $notifications = NotificationOnUserQuery::create()
            ->filterByUserId($currentUser->id)
            ->filterByStatus('Unread')
            ->leftJoin('Notification')
            ->withColumn('Notification.Time', 'time')
            ->withColumn('Notification.Type', 'type')
            ->withColumn('Notification.Data', 'data')
            ->where('Notification.Status = ?', 'Active')
            ->select(array(
                'id'
            ))
            ->orderBy('time', 'DESC');

        $notifications = $notifications->paginate($page, $limit);

        $total = $notifications->getNbResults();
        
        $data = [];
        foreach($notifications as $notification) {
            $notification = (object) $notification;
            switch ($notification->type) {
                case 'price':
                    $notification->data = json_decode($notification->data);
                    
                    $stock = StockQuery::create()->findOneById($notification->data->stock_id, $con);
                    
                    if ($stock) {
                        $notification->data->product_id = $stock->getProduct()->getId();
                        $notification->data->product_name = $stock->getProduct()->getName();
                        $notification->data->unit_name = $stock->getUnit()->getName();
                    } else {
                        $notification->delete($con);
                    }
            }
            $data[] = $notification;
        }
        
        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function loadOptionPrice($params, $currentUser, $con)
    {
        $options = NotificationOptionQuery::create()
            ->filterByType('price')
            ->leftJoin('Role')
            ->withColumn('Role.Name', 'role_name')
            ->select(array(
                'id',
                'role_id',
                'role_name',
            ))
            ->orderBy('role_name', 'ASC')
            ->find($con);
        
        $data = [];
        foreach($options as $option) {
            $data[] = $option;
        }
        
        $results['success'] = true;
        $results['data'] = $data;

        return $results;
    }

    public static function subOptionPrice($params, $currentUser, $con)
    {
        $options = NotificationOptionQuery::create()
            ->filterById($params->id)
            ->find($con);

        if (!$options) throw new \Exception('Data tidak ditemukan');

        foreach($options as $option)
        {
            $option->delete($con);
        }

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

}