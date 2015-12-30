# PHPMICROLIB

A collection of simple to use, lightweight PHP files that solve common scenario's like templating, routing and database-model mapping. The purpose of these collection is to be focused on one particular problem and to be independant of other files so they can be used by simply requiring them. They also try to keep as close to *normal* PHP as possible, for example the routes in the router are *normal* PHP regular expressions. This way you can keep using your knownledge of PHP and keep learning.

## Routing

* The first part of the route is the name of the controller. For example `/customers/12/orders` maps to the CustomersController in the CustomersController.php file.
* The controller maps one or more routes to methods.
* The controller evaluates the route and the first match will be executed.
* Only when this match returns `false`, the next one is also evaluated and executed if it matches. This way you can secure for example an admin area easily. See example below.

Simple example:

```php
class AdminController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      // route /customers
      '@^customers$@' => 'showCustomers',
      // possible route: /customers/123
      '@^admin/customers/(?<id>\d+)$@' => array(
        'get'=> 'editCustomer',
        'post' => 'updateCustomer'
      )
    ));
  }

  protected function showCustomers() {
    die('Customers page');
  }
  
  protected function editCustomer($args) {
    die('Edit customer ' . $args['id'] . ' from a GET');
  }
  
  protected function updateCustomer($args) {
    die('Update customer ' . $args['id'] . ' from a POST');
  }

}
```

Example of handing over to other controllers and securing a path:

```php
class AdminController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      // Every route that starts with /admin and then a word ending,
      // so /admin and /admin/customers match, but /administrator not.
      '@^admin\b@' => function() {
        if (!$loggedIn) {
          // User is not logged in so redirect to login page.
          header('Location: ' . Route::getFrontControllerPath() . '/login');
          exit;
        }
        // User is logged in.
        // Return false, so the next route is also evaluated.
        return false;
      },
      // route: /admin/customers
      '@^admin/customers\b@' => function() {
        self::handleRoute('CustomersController');
      }
    ));
  }

}
```

To start using the router, create the controllers you need and call the code below from within your front controller:

```php
require_once('router.inc.php');
Route::initialize();
// After initialize you can query some things, like the parts of the route
// This way you can for example handle some routes different than others.
try {
  Route::handleRoute('controllers'); // directory of controllers as argument
} catch (Exception $ex) {
  // Handle exception
}
```

## Templating



## Database mapping

