<?php

use \PHPMICROLIB\Router\Route;

class ApiController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      '@^api$@' => 'index'
    ));
  }

  protected function index() {
    die("<a href='" . Route::getFrontControllerPath() . "/about/12'>About...</a>");
  }

}
