<?php

use \PHPMICROLIB\Router\Route;

require_once(Route::getFrontControllerDir() . '/models/customer_pdo.inc.php');

class CustomersController extends \PHPMICROLIB\Router\Controller {
  
  private $pdoCustomer;

  function __construct() {
    parent::__construct(array(
      '@^customers$@' => 'showCustomers',
      '@^customers/(?<id>\d+)$@' => array(
        'get' => 'editCustomer',
        'post' => 'updateCustomer'
      ),
      '@^customers/(?<id>\d+)\b@' => function() {
        self::handleRoute('CustomerOrdersController');
      }
    ));
    $this->pdoCustomer = new PDOCustomer();
  }

  protected function showCustomers() {
    $customers = array_map(function($customer) {
      return $customer->email;
    }, $this->pdoCustomer->getAll());
    
    die('Customers: ' . implode(', ', $customers));
  }
  
  protected function editCustomer($args) {
    die('Edit customer ' . $args['id']);
  }
  
  protected function updateCustomer($args) {
    die('Update customer ' . $args['id'] . ' from POST!');
  }

}