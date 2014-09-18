<?php

namespace App;

use ORM\RoleQuery;

use Propel\Runtime\Propel;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\Wamp\Exception;
use Util\ExceptionThrower;

class Mains implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $from) {
        $from->Session->start();

        // Store the new connection to send messages to later
        $this->clients->attach($from);

        echo "New connection! (res_id: {$from->resourceId}, {$from->Session->getName()}: {$from->Session->getId()})\n";
    }

    public function onClose(ConnectionInterface $conn) {
        require_once 'propel-config.php';
        $con = Propel::getConnection('pos');
        $con->rollBack();

        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        require_once 'propel-config.php';
        $con = Propel::getConnection('pos');
        $con->rollBack();

        echo "Client {$conn->resourceId} hit an error: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()} \n";

        $data['success'] = false;
        $data['errmsg'] = $e->getMessage();

        $results['event'] = $conn->event;
        $results['data'] = $data;

        $conn->send(json_encode($results));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $event = $from->event = 'anonymous';
        $data = [];

        // if message did not exist then deny access
        if (!isset($msg) || $msg == '') throw new Exception('Wrong turn buddy');

        // if event or data did not exist then deny access
        $msg = json_decode($msg);
        if (!$msg || !isset($msg->event) || !isset($msg->data)) throw new Exception('Wrong turn buddy');

        // store baby store em to variables
        $event = $from->event = $msg->event;
        $params = $msg->data;

        // if user not loged in yet then deny access
        if ($from->Session->get('pos/state') != 1) throw new Exception('Akses ditolak. Anda belum login.');

        // initiating propel orm
        require_once 'propel-config.php';
        Propel::disableInstancePooling();
        $con = Propel::getConnection('pos');
        
        // if user don't have role assigned then deny access
        $role = RoleQuery::create()->findOneById($from->Session->get('pos/current_user')->role_id);
        if (!$role) throw new Exception('Akses ditolak. Anda belum punya role.');

        // uh... decoding event to get requested module and method
        $decode = explode('/', $event);
        
        // if decoded array length is not 2 then deny access
        if (count($decode) != 2) throw new Exception('Wrong turn buddy');

        // store baby store em to variables... again
        $module = $decode[0];
        $method = $decode[1];

        // list of all module that can be requested
        $registeredModule = array(
            'chart',
            'combo',
            'credit',
            'notification',
            'option',
            'populate',
            'product',
            'purchase',
            'report',
            'sales',
            'stock',
            'unit',
            'user'
        );

        // if requested module is not registered then deny access
        if (!in_array($module, $registeredModule)) throw new Exception('Wrong turn buddy');

        // you know it.. begin transaction
        $con->beginTransaction();

        // this is where magic begins..
        // route to requested module
        $data = $this->$module($method, $params, $from, $con);

        // commit transaction
        $con->commit();

        // store to results variable before spitting it out back to client
        $results['event'] = $event;
        $results['data'] = $data;

        // errmmmmm
        $from->send(json_encode($results));
        
        // followup action
        if (
            $event == 'purchase/create'
            ||
            $event == 'purchase/destroy'
            ||
            $event == 'purchase/update'
        ){
            // get update of last 30 Days transaction's data
            $data = $this->chart('last30DaysTransaction', new \stdClass(), $from, $con);
            $last30DaysTransaction['event'] = 'chart/last30DaysTransaction';
            $last30DaysTransaction['data'] = $data;
            
            // iterate through all connected client
            foreach ($this->clients as $client) 
            {
                // get notification update of each client
                $data = $this->notification('read', new \stdClass(), $client, $con);
                $results['event'] = 'notification/read';
                $results['data'] = $data;

                // push notification to each client
                $client->send(json_encode($results));
                
                // push last 30 Days transaction's data to each client
                $client->send(json_encode($last30DaysTransaction));
            }
        } elseif (
            $event == 'sales/create'
            ||
            $event == 'sales/destroy'
            ||
            $event == 'sales/update'
        ){
            // get update of last 30 Days transaction's data
            $data = $this->chart('last30DaysTransaction', new \stdClass(), $from, $con);
            $last30DaysTransaction['event'] = 'chart/last30DaysTransaction';
            $last30DaysTransaction['data'] = $data;
            
            // iterate through all connected client
            foreach ($this->clients as $client) 
            {   
                // push last 30 Days transaction's data to each client
                $client->send(json_encode($last30DaysTransaction));
            }
        }
        
        // finish
        return;
    }

    private function chart($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'customSalesVsPurchase',
            'last30DaysTransaction',
            'monthlySalesVsPurchase',
            'monthlyTransaction'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Charts::$method($params, $currentUser, $con);

        return $results;
    }

    private function combo($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'cashier',
            'customer',
            'product',
            'stock',
            'unit'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Combos::$method($params, $currentUser, $con);

        return $results;
    }

    private function credit($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'cancelPayment',
            'loadFormPay',
            'pay',
            'read',
            'readPayment'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Credits::$method($params, $currentUser, $con);

        return $results;
    }

    private function notification($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'destroy',
            'read',
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Notifications::$method($params, $currentUser, $con);

        return $results;
    }
    
    private function option($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'changePassword',
            'loadBiodata',
            'updateBiodata'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Options::$method($params, $currentUser, $con);

        return $results;
    }
    
    private function populate($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'stock'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Populate::$method($params, $currentUser, $con);

        return $results;
    }
    
    private function product($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'create',
            'destroy',
            'loadFormEdit',
            'read',
            'update'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Products::$method($params, $currentUser, $con);

        return $results;
    }

    private function purchase($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'cancel',
            'create',
            'loadFormEdit',
            'read',
            'update',
            'viewDetail'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Purchases::$method($params, $currentUser, $con);

        return $results;
    }

    private function report($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'monthly',
            'custom',
            'customPurchasedProduct',
            'customSaledProduct',
            'monthlyPurchasedProduct',
            'monthlySaledProduct'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Reports::$method($params, $currentUser, $con);

        // followup action
        if ($method == 'monthly') {
            
            // send Sales vs Purchase comparison
            $data = $this->chart('monthlySalesVsPurchase', $params, $from, $con);
            $salesVsPurchase['event'] = 'chart/monthlySalesVsPurchase';
            $salesVsPurchase['data'] = $data;
            
            $from->send(json_encode($salesVsPurchase));
            
            // send transaction's data on picked month
            $data = $this->chart('monthlyTransaction', $params, $from, $con);
            $transaction['event'] = 'chart/monthlyTransaction';
            $transaction['data'] = $data;
            
            $from->send(json_encode($transaction));
            
            // send saled product
            $data = Reports::monthlySaledProduct($params, $currentUser, $con);
            $saledProduct['event'] = 'report/monthlySaledProduct';
            $saledProduct['data'] = $data;
            
            $from->send(json_encode($saledProduct));
            
            // send purchased product
            $data = Reports::monthlyPurchasedProduct($params, $currentUser, $con);
            $purchasedProduct['event'] = 'report/monthlyPurchasedProduct';
            $purchasedProduct['data'] = $data;
            
            $from->send(json_encode($purchasedProduct));
        } 
        elseif ($method == 'custom') {
            
            // send Sales vs Purchase comparison
            $data = $this->chart('customSalesVsPurchase', $params, $from, $con);
            $salesVsPurchase['event'] = 'chart/customSalesVsPurchase';
            $salesVsPurchase['data'] = $data;
            
            $from->send(json_encode($salesVsPurchase));
            
            // send saled product
            $data = Reports::customSaledProduct($params, $currentUser, $con);
            $saledProduct['event'] = 'report/customSaledProduct';
            $saledProduct['data'] = $data;
            
            $from->send(json_encode($saledProduct));
            
            // send purchased product
            $data = Reports::customPurchasedProduct($params, $currentUser, $con);
            $purchasedProduct['event'] = 'report/customPurchasedProduct';
            $purchasedProduct['data'] = $data;
            
            $from->send(json_encode($purchasedProduct));
            
        }
        
        return $results;
    }

    private function sales($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'cancel',
            'create',
            'loadFormEdit',
            'read',
            'update',
            'viewDetail'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Sale::$method($params, $currentUser, $con);

        return $results;
    }

    private function stock($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'addVariant',
            'create',
            'destroy',
            'getOne',
            'loadFormEdit',
            'read',
            'update'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Stocks::$method($params, $currentUser, $con);

        return $results;
    }
    
    private function unit($method, $params, $from, $con){
        $results = [];
        
        // list of all method that can be called in current module
        $registeredMethod = array(
            'create',
            'destroy',
            'loadFormEdit',
            'read',
            'update'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Units::$method($params, $currentUser, $con);

        return $results;
    }

    private function user($method, $params, $from, $con){
        $results = [];
                
        // list of all method that can be called in current module
        $registeredMethod = array(
            'create',
            'destroy',
            'loadFormEdit',
            'read',
            'resetPassword',
            'update'
        );

        // if called method is not registered then deny access
        if (!in_array($method, $registeredMethod)) throw new Exception('Wrong turn buddy');

        // get Current User
        $currentUser = $from->Session->get('pos/current_user');

        // route to requested module and method
        $results = Users::$method($params, $currentUser, $con);

        return $results;
    }

}