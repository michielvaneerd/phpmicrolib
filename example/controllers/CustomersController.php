<?php

class CustomersController extends \PHPMICROLIB\Router\Controller {

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
  }

  protected function showCustomers() {
    die('Customers page');
  }
  
  protected function editCustomer($args) {
    die('Edit customer ' . $args['id']);
  }
  
  protected function updateCustomer($args) {
    die('Update customer ' . $args['id'] . ' from POST!');
  }

}