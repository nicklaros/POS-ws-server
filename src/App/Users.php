<?php

namespace App;

use ORM\Role;
use ORM\RoleQuery;
use ORM\RolePermission;
use ORM\RolePermissionQuery;
use ORM\User;
use ORM\UserQuery;
use ORM\UserDetail;
use ORM\UserDetailQuery;

class Users
{

    public static function create($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('create_user')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        // check if picked username is already used by anyone
        $user = UserQuery::create()->filterByUser($params->user)->count($con);
        if ($user != 0) throw new \Exception('User ID sudah terpakai. Pilih User ID lainnya.');

        $role = RoleQuery::create()->findOneById($currentUser->role_id);

        // create new user
        $user = new User();
        $user->setUser($params->user)
            ->setPassword(hash('sha512', $params->user))
            ->setRoleId($params->role_id)
            ->setStatus($role->getName() == 'Super User' ? 'Active' : 'Pending')
            ->save($con);

        // create user detail
        $userDetail = new UserDetail();
        $userDetail->setId($user->getId())
            ->setName($params->name)
            ->setAddress($params->address)
            ->setPhone($params->phone)
            ->save($con);

        // insert into row_history table
        $rowHistory = new RowHistory();
        $rowHistory->setRow($user->getId())
            ->setData('user')
            ->setTime($time)
            ->setOperation('create')
            ->setUserId($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['id'] = $user->getId();

        return $results;
    }

    public static function destroy($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('destroy_user')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        if (in_array(1, $params->id)) throw new \Exception('Default Admin tidak boleh dihapus!');

        $users = UserQuery::create()
            ->filterById($params->id)
            ->find($con);

        if (!$users) throw new \Exception('Data tidak ditemukan');

        foreach($users as $user)
        {
            $user->setUser('')->setStatus('Deleted')->save($con);

            $rowHistory = new RowHistory();
            $rowHistory->setRow($row->getId())
                ->setData('user')
                ->setTime($time)
                ->setOperation("destroy")
                ->setUser($currentUser->id)
                ->save($con);
        }

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

    public static function loadFormEdit($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_user')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $user = UserQuery::create()
            ->filterByStatus('Active')
            ->select(array(
                'id',
                'user',
                'role_id',
                'status'
            ))
            ->leftJoin('Detail')
            ->withColumn('Detail.Name', 'name')
            ->withColumn('Detail.Address', 'address')
            ->withColumn('Detail.Phone', 'phone')
            ->leftJoin('Role')
            ->withColumn('Role.Name', 'role')
            ->findOneById($params->id);

        if (!$user) throw new \Exception('Data tidak ditemukan');

        $results['success'] = true;
        $results['root'] = $user;

        return $results;
    }

    public static function read($params, $currentUser, $con)
    {
        $page = (isset($params->page) ? $params->page : 0);
        $limit = (isset($params->limit) ? $params->limit : 100);

        $user = UserQuery::create()->filterByStatus('Active');

        if(isset($params->name)) $user->useDetailQuery()->filterByName("%$params->name%")->endUse();
        if(isset($params->role_id)) $user->filterByRoleId($params->role_id);

        $user
            ->select(array(
                'id',
                'user',
                'role_id',
                'status'
            ))
            ->leftJoin('Detail')
            ->withColumn('Detail.Name', 'name')
            ->withColumn('Detail.Address', 'address')
            ->withColumn('Detail.Phone', 'phone')
            ->leftJoin('Role')
            ->withColumn('Role.Name', 'role');

        foreach($params->sort as $sorter){
            $user->orderBy($sorter->property, $sorter->direction);
        }

        $user = $user->paginate($page, $limit);

        $total = $user->getNbResults();

        $data = [];
        foreach($user as $row) {
            $data[] = $row;
        }

        $results['success'] = true;
        $results['data'] = $data;
        $results['total'] = $total;

        return $results;
    }

    public static function resetPassword($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('reset_pass_user')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $user = UserQuery::create()->findOneById($params->id, $con);
        if (!$user) throw new \Exception('Data tidak ditemukan');

        $user->setPassword(hash('sha512', $user->getUser()))
            ->save($con);

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

    public static function update($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_user')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        if ($params->id == 1 && $params->user != 'admin') throw new \Exception('User ID Default Admin tidak boleh diubah.');
        if ($params->id == 1 && $params->role_id != 1) throw new \Exception('Role Default Admin tidak boleh diubah.');

        // check whether picked username is already taken
        $user = UserQuery::create()
            ->filterByUser($params->user)
            ->where("id not like ?", $params->id)
            ->count($con);
        if ($user != 0) throw new \Exception('User ID sudah terpakai. Pilih User ID lainnya.');

        $user = UserQuery::create()->findOneById($params->id, $con);
        $detail = UserDetailQuery::create()->findOneById($params->id, $con);
        if(!$user || !$detailUser) throw new \Exception('Data tidak ditemukan');

        $user->setUser($params->user)
            ->setRoleId($params->role_id)
            ->save($con);

        $detail->setName($params->name)
            ->setAddress($params->address)
            ->setPhone($params->phone)
            ->save($con);

        $rowHistory = new RowHistory();
        $rowHistory->setRow($params->id)
            ->setData('user')
            ->setTime($time)
            ->setOperation('update')
            ->setUser($currentUser->id)
            ->save($con);

        $results['success'] = true;
        $results['id'] = $params->id;

        return $results;
    }

}