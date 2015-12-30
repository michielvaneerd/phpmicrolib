<?php

use \PHPMICROLIB\Router\Route;

class AdminController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      '@^admin\b@' => function() {
        if (!$loggedIn) {
          header('Location: ' . Route::getFrontControllerPath() . '/login');
          exit;
        }
        // Return false, so the next route is also evaluated.
        return false;
      },
      '@^admin$@' => 'showAdminIndex'
    ));
  }

  protected function showAdminIndex() {
    die('Admin index page');
  }

}