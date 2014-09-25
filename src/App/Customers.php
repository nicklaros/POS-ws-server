<?php

namespace App;

use ORM\CreditQuery;
use ORM\Customer;
use ORM\CustomerQuery;
use ORM\RolePermissionQuery;
use ORM\RowHistory;
use ORM\SalesQuery;

class Customers
{

    private static function getStats($params, $currentUser, $con)
    {
        $data = [];
        
        // sales this month
        $start = new \DateTime(Date('Y-m-01'));
        $until = new \DateTime(Date('Y-m-t'));
        
        $sales = SalesQuery::create()
            ->filterByStatus('Active')
            ->filterByCustomerId($params->customer_id)
            ->filterByDate(array('min' => $start, 'max' => $until))
            ->withColumn('COUNT(Sales.Id)', 'sales_count')
            ->withColumn('SUM(Sales.TotalPrice)', 'sales_total')
            ->select([
                'sales_count',
                'sales_total'
            ])
            ->find($con);

        $data['sales_count_this_month'] = (isset($sales[0]['sales_count']) ? $sales[0]['sales_count'] : 0);
        $data['sales_total_this_month'] = (isset($sales[0]['sales_total']) ? $sales[0]['sales_total'] : 0);
        
        // sales this year
        $start = new \DateTime(Date('Y-01-01'));
        $until = new \DateTime(Date('Y-12-31'));
        
        $sales = SalesQuery::create()
            ->filterByStatus('Active')
            ->filterByCustomerId($params->customer_id)
            ->filterByDate(array('min' => $start, 'max' => $until))
            ->withColumn('COUNT(Sales.Id)', 'sales_count')
            ->withColumn('SUM(Sales.TotalPrice)', 'sales_total')
            ->select([
                'sales_count',
                'sales_total'
            ])
            ->find($con);

        $data['sales_count_this_year'] = (isset($sales[0]['sales_count']) ? $sales[0]['sales_count'] : 0);
        $data['sales_total_this_year'] = (isset($sales[0]['sales_total']) ? $sales[0]['sales_total'] : 0);
        
        $credits = CreditQuery::create()
            ->filterByStatus('Active')
            ->useSalesQuery()
                ->filterByCustomerId($params->customer_id)
            ->endUse()
            ->withColumn('CONVERT(Credit.Total, SIGNED) - CONVERT(Credit.Paid, SIGNED)', 'balance')
            ->select(array(
                'balance'
            ))
            ->find($con);
        
        $credit_total = 0;
        foreach($credits as $credit) {
            if ($credit > 0) {
                $credit_total += $credit;
            }
        }
        
        $data['credit'] = $credit_total;
        
        $data['customer_id'] = $params->customer_id;

        $results['data'] = $data;

        return $results;
    }

    private static function seeker($params, $currentUser, $con)
    {
        $customer = CustomerQuery::create()
            ->filterByStatus('Active')
            ->withColumn('DATE_FORMAT(NOW(), "%Y") - DATE_FORMAT(Customer.Birthday, "%Y") - (DATE_FORMAT(NOW(), "00-%m-%d") < DATE_FORMAT(Customer.Birthday, "00-%m-%d"))', 'age')
            ->select(array(
                'id',
                'registered_date',
                'name',
                'address',
                'birthday',
                'gender',
                'phone'
            ))
            ->findOneById($params->customer_id);

        if (!$customer) throw new \Exception('Data tidak ditemukan');
        
        if ($customer['age'] > 200) $customer['age'] = '-';

        $results['data'] = $customer;

        return $results;
    }
    
    public static function last7MonthsTransactions($params, $currentUser, $con)
    {
        $date = new \DateTime(Date('Y-m-01'));
        $date->sub(new \DateInterval('P7M'));

        $data = [];
        
        for($i=1; $i<=7; $i++){
            $date->add(new \DateInterval('P1M'));

            $sales = SalesQuery::create()
                ->filterByStatus('Active')
                ->filterByCustomerId($params->customer_id)
                ->filterByDate(array('min' => $date->format('Y-m-01'), 'max' => $date->format('Y-m-t')))
                ->withColumn('SUM(Sales.TotalPrice)', 'sales_total')
                ->select([
                    'sales_total'
                ])
                ->find($con);

            $row = [
                'month' => $date->format('Y-m-01'),
                'sales' => (isset($sales[0]) ? $sales[0] : 0)
            ];
            
            $data[] =  $row;
        }

        $results['success'] = true;
        $results['data'] = $data;

        return $results;
    }

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

    public static function listSales($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_customer')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');
        
        if (!isset($params->customer_id)) throw new \Exception('Missing parameter');
        
        $start = new \DateTime(Date('Y-m-01'));
        $until = new \DateTime(Date('Y-m-t'));

        $sales = SalesQuery::create()
            ->filterByStatus('Active')
            ->filterByCustomerId($params->customer_id)
            ->filterByDate(array('min' => $start, 'max' => $until))
            ->leftJoin('Customer')
            ->leftJoin('Cashier')
            ->withColumn('Customer.Name', 'customer_name')
            ->withColumn('Cashier.Name', 'cashier_name')
            ->select(array(
                'id',
                'date',
                'customer_id',
                'total_price',
                'cashier_id'
            ))
            ->orderBy('date', 'ASC')
            ->orderBy('id', 'ASC')
            ->find($con);
        
        $data = [];
        foreach($sales as $sale) {
            $data[] = $sale;
        }

        $results['success'] = true;
        $results['data'] = $data;

        return $results;
    }

    public static function loadFormEdit($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('update_customer')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $params->customer_id = $params->id;
        
        $customer = Customers::seeker($params, $currentUser, $con);

        $results['success'] = true;
        $results['data'] = $customer['data'];

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

    public static function viewDetail($params, $currentUser, $con)
    {
        // check role's permission
        $permission = RolePermissionQuery::create()->select('read_customer')->findOneById($currentUser->role_id, $con);
        if (!$permission || $permission != 1) throw new \Exception('Akses ditolak. Anda tidak mempunyai izin untuk melakukan operasi ini.');

        $customer = Customers::seeker($params, $currentUser, $con);
        
        $stats = Customers::getStats($params, $currentUser, $con);
        
        $results['success'] = true;
        $results['detail'] = $customer['data'];
        $results['stats'] = $stats['data'];

        return $results;
    }

}