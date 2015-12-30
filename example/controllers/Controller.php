<?php

use \PHPMICROLIB\Router\Route;

class Controller extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      '@^$@' => 'index'
    ));
  }

  protected function index() {
    die("<a href='" . Route::getFrontControllerPath() . "/customers'>Show customers</a><br>
        <a href='" . Route::getFrontControllerPath() . "/customers/12'>Edit customer 12</a><br>
        <form method='post' action='" . Route::getFrontControllerPath() . "/customers/12'><input type='submit' value='Update customer 12' /></form><br>
        <a href='" . Route::getFrontControllerPath() . "/customers/12/orders'>Show orders from customer</a>");
  }

}
