# PHPMICROLIB

Simple to understand and lightweight files that handle scenario's like routing, templating and ORM.

Below you find some getting started information. For more complete and advanced use cases, see the example.

## Routing

Start the routing by placing this code into the front controller:

```php
require_once('router.inc.php');
Route::initialize();
try {
  Route::handleRoute('controllers'); // directory of controllers as argument
} catch (Exception $ex) {
  // Handle exception
}
```

And then create some controllers.

Some controller properties:

* The name of the controller is determined by the first part of the route. For example `/customers` and `/customers/123/orders` both will use the `CustomersController` in the `CustomersController.php` file.
* Inside the controller, map all the routes to functions.

__Example 1:__

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

__Example 3 - secure admin area:__

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
      }
    ));
  }

}
```

## Templating

__Example 1:__

PHP file that fills the template:
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

The `PDOModel` class maps PHP models to database models and in reverse. To use it, define a PHP class and an accompanying `PDOModel` instance.

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

__Example 2 - encryption:__
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

__Example 3 - model has properties from other tables:__
By default the `PDOModel` will select all properties of the model from the table. If you use properties on the model that are not in the table, you have to define a select query for yourself and put these non-table properties into the foreignProperties array.

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