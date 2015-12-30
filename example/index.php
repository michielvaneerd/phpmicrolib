<?php

// For demo purpose only - this should be in a separate config file
// outside of a web accessible directory.
define('SECRET_KEY', '03f731f951edd35c4917d644f8484b6e');

// Include the libraries we need
require_once('inc/utils.inc.php');
require_once('../src/template.inc.php');
require_once('../src/router.inc.php');
require_once('../src/database.inc.php');
require_once('../src/crypt.inc.php');

use PHPMICROLIB\Template;
use PHPMICROLIB\Database\Crypt;
use PHPMICROLIB\Database\PDOModel;
use PHPMICROLIB\Router\Route;

// Set the connection parameters
PDOModel::setConnectionParameters('localhost', 'testuser', '', 'myapp');

// Reden om eerst te initializeren en dan pas handleRoute te doen, is
// dat je dan na initialisatie bijv. nog kunt kijken of je een andere
// directory op wil geven aan de handleroute methode. Bijv. als eerste
// deel van de path "api" is, dan altijd in de map "api" halen...
Route::initialize();
$pathParts = Route::getPathParts();
$isApi = !empty($pathParts) && $pathParts[0] === 'api';
try {
  if ($isApi) {
    Route::handleRoute('controllers/api');
  } else {
    Route::handleRoute('controllers');
  }
  throw new Exception("No controller found for this route!");
} catch (Exception $ex) {
  if ($isApi) {
    header('Content-Type:application/json');
    die(json_encode(array('error' => $ex->getMessage())));
  } else {
    $tpl = new Template('templates/app.tpl.php');
    $tpl->set('title', 'Error');
    $tpl->set('exception', $ex->getMessage());
    $tpl->set('content', '');
    die($tpl->parse());
  }
}

?>