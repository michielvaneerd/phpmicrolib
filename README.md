# PHPMICROLIB

A collection of simple to understand and lightweight files that handle common scenario's like routing, templating and ORM.

See below for getting started information. For more complete and advanced use cases, see the examples.

## Routing

File: `router.inc.php`.

Start the routing by placing the code below into the front controller:

```php
require_once('router.inc.php');
Route::initialize();
try {
  // In this case the controllers are in the "controllers" directory
  Route::handleRoute('controllers');
} catch (Exception $ex) {
  // Handle exception
}
```

This will parse the incoming route and call the accompanying controller. The name of the controller is determined by the first part of the route. For example `/customers` and `/customers/123/orders` both will call the `CustomersController` in the `CustomersController.php` file.

__Example 1: simple use case__

```php
class CustomersController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      '@^customers$@' => 'showCustomers',
      '@^customers/new$@' => array(
        'get' => function() {
          die('Show form to create new customer');
        },
        'post' => function() {
          die('Create a new customer based on the POST request');
        }
      ),
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

Some things to note:
* Each controller extends the abstract Controller class.
* Each controller calls the parents constructor with an array of routes and associated methods.
* The routes are regular expressions and are matched from the front controller. So if index.php is the front controller, then the route `@^customers$@` will match `index.php/customers` and `@customers/(\d+)@` will match `index.php/customers/12` (if you have a rewrite rule active, you can of course also get rid of the front controller).
* The value of the routes array can be one of the following:
  * string with name of method (minimal protected visibility)
  * a closure with the code to execute
  * an array with the request methods strings / closures

__Example 2 - handover control to another controller:__

File CustomersController.php:
```php
class CustomersController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      '@^customers/(?<id>\d+)$@' => 'editCustomer',
      '@^customers/(?<id>\d+)/orders\b@' => function() {
        self::handleRoute('OrdersController');
      }
    ));
  }

}
```

File OrdersController.php:
```php
class OrdersController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      '@^customers/(?<id>\d+)$@' => 'editCustomer',
      '@^customers/(?<id>\d+)/orders\b@' => function() {
        self::handleRoute('OrdersController');
      }
    ));
  }

}
```

Some things to note:
* By calling another controller from within a controller, nested controllers are possible. This way your controllers can stay simple while still allowing your application to have deep nested routes.
* As you can see in the example above, every route that starts with `^customers/(?<id>\d+)/orders\b` will be handover to the OrdersController. Pay attention to the "wordend" regular expression `\b`, so `customers/12/orders` and `customers/12/orders/100` will match, but `customers/12/ordersold` will not.

__Example 3 - secure admin area:__

```php
class AdminController extends \PHPMICROLIB\Router\Controller {

  function __construct() {
    parent::__construct(array(
      '@^admin\b@' => function() {
        if (!$loggedIn) {
          header('Location: ' . Route::getFrontControllerPath() . '/login');
          exit;
        }
        return false;
      },
      '@^admin/users$@' => 'showUsers'
    ));
  }
  
  protected function showUsers() {
    // We are logged in and can show the users
  }

}
```

Some things to note:
* By adding `^admin\b` as the first route, this one is evaluated first. If the user is not logged in, we redirect. If the user _is_ logged in, we return `false` so the next route(s) are also evaluated.
* In this case, when the route is `admin/users`, first the first route is evaluated, and if this one returns false, the second one is also evaluated. So thids way it is easy to add a secure area in one place.


## Templating

__Example 1:__

File: `template.inc.php`

PHP file that uses the template:

```php
require_once('template.inc.php');
$tpl = new Template('/path/to/template.tlp.php');
$tpl->set('title', 'Template file');
$tpl->set('customers', array('Pete', 'Carl', 'Mitch'));
die($tpl->parse());
```

Template file (this is in fact just a regular PHP file):

```html
<html>
  <head>
    <title><?php echo $this->esc($title); ?></title>
  </head>
  <body>
    <ul>
    <?php foreach ($customers as $customer): ?>
      <li><?php echo $this->esc($customer); ?></li>
    <?php endforeach; ?>
    </ul>
  </body>
</html>
```


## Database mapping

File: `database.inc.php`

The `PDOModel` class maps PHP models to database models and vice versa. To use it, define a PHP class and an accompanying `PDOModel` instance.

__Example 1:__
```php
require_once('database.inc.php');

class Customer {
  public $id;
  public $username;
  public $pwd;
}

class PDOCustomer extends PDOmodel {
  protected $className = 'Customer';
  
  public function getAll() {
    return $this->getCollection($this->selectQuery);
  }
  
}
```

Some things to note:
* The `Customer` class is the class we want to use in PHP.
* The `PDOCustomer` class is the class that is used to map between database table and PHP class.
* From within the PDO class, set the PHP classname in the `$className` property.

__Example 2 - encryption:__

You can easily add encryption to your models.

```php
require_once('crypt.inc.php');

// Of course don't define it like this in production :-)
define('SECRET_KEY', '03f731f951edd35c4917d644f8484b6e');

class Customer {
  public $id;
  public $username;
  public $pwd;
  public $email;
}

class PDOCustomer extends PDOmodel {
  protected $className = 'Customer';
  protected $encryptedProperties = ['email'];
  
  function __construct() {
    parent::__construct();
    $this->setCrypt(new Crypt(SECRET_KEY));
  }
  
  public function getAll() {
    return $this->getCollection($this->selectQuery);
  }
  
}
```

Some things to note:
* Make sure to instantiate a class that implements the `PDOCrypt` interface as defined in the database.inc.php file. An implementation of this interface is added in the crypt.inc.php file. In the constructor of the PDO class, set this `PDOCrypt` instance.
* Make sure to add the encrypted properties to the `$encryptedProperties` array.
* Add all the functions you need, for example the `getAll()` method returns all customers.

__Example 3 - model has properties from other tables:__

By default the `PDOModel` will select all properties of the model from the table. If you use properties on the model that are not in the table, you have to define a select query for yourself.

```php
class Customer {
  public $id;
  public $username;
  public $pwd;
  public $firstname;
  // Property not in table:
  public $street;
}

class PDOCustomer extends PDOmodel {
  
  protected $className = 'Customer';
  protected $foreignProperties = ['street'];
  protected $selectQuery = "SELECT c.*, a.street
      FROM customer AS c
      LEFT JOIN address AS a
        ON a.customer_id = c.id";
  
  public function getAll() {
    return $this->getCollection($this->selectQuery);
  }
  
}
```

Some things to note:
* Define your own `$selectQuery` - by default this will be a simple select of all model properties.
* Add all the properties that are on the model, but not in the table to the `$foreignProperties` array.