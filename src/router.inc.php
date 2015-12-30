<?php

/**
 * The router and controller classes.
 * This file should always be required from within the front controller.
 */

namespace PHPMICROLIB\Router;

/**
 * Route class.
 */
class Route {
  
  /** Singleton Route instance */
  private static $instance;
  /** System directory of the front controller */
  private static $frontControllerDir;
  /** URL directory of the front controller */
  private static $frontControllerPath;
  /** Current path relative to the front controller */
  private static $path;
  /** Parts of $path */
  private static $pathParts;

  /**
   * Private constructor to force singleton pattern.
   */  
  private function __construct($path) {
    // SCRIPT_FILENAME is absolute filesystem path of executing script
    // (so front controller index.php).
    self::$frontControllerDir = dirname($_SERVER['SCRIPT_FILENAME']);
    // SCRIPT_NAME is absolute URL path of executing script.
    self::$frontControllerPath = dirname($_SERVER['SCRIPT_NAME']);
    // REQUEST_URI can be called with or without the front controller.
    // E.g. /bsf/index.php/some/path or /bsf/some/path
    if ($path === null) {
      $path = '';
      if (array_key_exists('REQUEST_URI', $_SERVER)) {
        if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
          $path = substr($_SERVER['REQUEST_URI'],
            strlen($_SERVER['SCRIPT_NAME']));
        } else {
          $path = substr($_SERVER['REQUEST_URI'],
            strlen(self::$frontControllerPath));
        }
        $path = empty($_SERVER['QUERY_STRING'])
          ? $path
          : substr($path, 0, -(strlen($_SERVER['QUERY_STRING']) + 1)); 
      }
    }
    self::$path = trim($path, '/?&');
    self::$pathParts = explode('/', self::$path);
  }

  /**
   * Creates the singleton and initializes the routes variables.
   *
   * After this has been done, you can use the static getters, like
   * getPath().
   * @return void
   */  
  public static function initialize($path = null) {
    if (self::$instance === null) {
      self::$instance = new Route($path);
    }
  }
  
  /**
   * Returns current path relative to the front controller
   * @return string Current path relative to the front controller.
   */
  public static function getPath() {
    return self::$path;
  }
  
  /**
   * Returns parts of current path as an array.
   * @return array parts of current path as an array.
   */
  public static function getPathParts() {
    return self::$pathParts;
  }
 
  /**
   * Returns URL directory of the front controller.
   * @return string URL directory of the front controller
   */ 
  public static function getFrontControllerPath() {
    return self::$frontControllerPath;
  }
  
  /**
   * Returns system directory of the front controller
   * @return string System directory of the front controller
   */
  public static function getFrontControllerDir() {
    return self::$frontControllerDir;
  }

  public static function getBaseControllerName() {
    $parts = self::getPathParts();
    $ctrlName = ucfirst(array_shift($parts)) . 'Controller';
    return $ctrlName;
  }
 
  /**
   * Handles the current route by handing it over to the controller in charge
   * for this route.
   * @param string $controllerDirectory The directory the controllers are in.
   * The directory should be without ending directory separator.
   * @return void
   */ 
  public static function handleRoute($controllerDirectory) {
    $ctrlName = self::getBaseControllerName();
    Controller::handleRoute($ctrlName, $controllerDirectory);
  }
  
}

/**
 * Abstract Controller class. All controllers should extend this.
 */
abstract class Controller {

  /** Array of routes. */
  private $routes;
  
  private static $controllerDirectory;
 
  /**
   * Constructor
   *
   * The constructor accepts an array with routes and actions to perform.
   * The key is an regular expression and the value can be a string with
   * a method name of the controller or a closure.
   * You can also specify different actions for the request types, like
   * get and post.
   *
   * <pre>$routes = array(
   *   '@^login$@' => 'loginAction' // points to loginAction method
   * );
   * $routes = array(
   *   '@^customers/(?&lt;customerId>\d+)$@' => function($args) {
   *     $this->handleCustomer($args['customerId']);
   *   }
   * );
   * $routes = array(
   *   'route' => array(
   *     'get' => 'method_name', // or closure
   *     'post' => 'method_name'
   *   )
   * );</pre>
   *
   * @param array $routes The routes you want this controller to handle.
   */ 
  function __construct($routes = null, $controllerDirectory = null) {
    $this->routes = $routes;
    $this->controllerDirectory = $controllerDirectory;
  }

  
  /**
   * Evaluates all routes from the controller. The first found
   * route is executed. If this route does *not* return false,
   * the script is exited.
   * @return void
   */
  public function route() {
    foreach ($this->routes as $preg => $method) {
      $matches = null;
      if (preg_match($preg, Route::getPath(), $matches)) {
        $result = null;
        if (is_array($method)) {
          $requestType = strtolower($_SERVER['REQUEST_METHOD']);
          if (array_key_exists($requestType, $method)) {
            $func = $method[$requestType];
            if (is_callable($func)) {
              $result = $func($matches);
            } else {
              $result = $this->$func($matches);
            }
          }
        } elseif (is_callable($method)) {
          $result = $method($matches);
        } else {
          $result = $this->$method($matches);
        }
        if ($result !== false) {
          exit;
        }
      }
    }
  }
  
  /**
   * Loads and executes a matched route from controller.
   * @param string $ctrlName The name of the controller file without .php
   * extension (this should be the same as the name of the class).
   * @param string $controllerDirectory The directory the controllers are in.
   * The directory should be without ending directory separator.
   * @return void
   */
  public static function handleRoute($ctrlName, $controllerDirectory = null) {
    if ($controllerDirectory !== null) {
      self::$controllerDirectory = $controllerDirectory;
    }
    $path = self::$controllerDirectory . '/' . $ctrlName . '.php';
    if (file_exists($path)) {
      require_once($path);
      $ctrl = new $ctrlName();
      $ctrl->route();
    }
  }

  /**
   * Returns the routes.
   * @return array Array of routes.
   */
  public function getRoutes() {
    return $this->routes;
  }

}

?>
