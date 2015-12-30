<?php

// Properties moeten public zijn!
// Want de map functie zet deze.
// Ook mooier om hier aparte functies van te maken, zodat de Customer
// class lean and mean blijft. Deze maak je namelijk veel vaker en een DB
// class heb je maar weinig nodig. Hierdoor blijven de Customer e.d. classes
// klein en lichtgewicht.
// Nadeel is dat de foreignProperties e.d. niet direct in de class staan
// maar in de PDO class... maar eigenlijk is dat goed, want nu is class
// helemaal gescheiden van database class.
class Customer {
  
  public $id;
  public $username;
  public $pwd;
  public $email;
  public $firstname;
  
  public $street;
  
}

class PDOCustomer extends PDOmodel {
  
  protected $className = 'Customer';
  protected $foreignProperties = ['street'];
  protected $encryptedProperties = ['email'];
  protected $selectQuery = "SELECT c.*, a.street
      FROM customer AS c
      LEFT JOIN address AS a
        ON a.customer_id = c.id";
  
  public function getAll() {
    return $this->getCollection($this->selectQuery);
  }
  
  function __construct() {
    parent::__construct();
    $this->setCrypt(new Crypt(SECRET_KEY));
  }
  
}