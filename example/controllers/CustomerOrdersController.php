<?php

class CustomerOrdersController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      '@^customers/(?<id>\d+)/orders$@' => function($args) {
        die('Orders from customer ' . $args['id']);
      }
    ));
  }

}