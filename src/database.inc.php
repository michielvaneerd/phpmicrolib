<?php

namespace PHPMICROLIB\Database;

abstract class PDOModel {
  
  // The next members will be set or overruled in child class:
  
  // Required. Classname this PDOModel should map to.
  protected $className;
  // Optional. When not set, the lowercase $className is used.
  protected $tableName;
  // Optional. Properties from the model that are not in the table.
  protected $foreignProperties = array();
  // Optional. Properties that should be encrypted when inserted into the
  // database, and decrypted when retrieved from the database.
  protected $encryptedProperties = array();
  // Optional. When not set, default to select all $tableProperties. Set this
  // when doing joins and using $foreignProperties.
  protected $selectQuery;
  
  
  // Default PDO instance of all PDOModel instances.
  private static $staticPdo = null;
  
  // Can be used to overrule the default static one.
  private $pdoInstance;
  
  // Properties of the model this PDOModel maps to that are also in the table.
  private $tableProperties;
  // All properties of the model this PDOModel maps to.
  private $modelProperties;
  // PDOCrypt instance.
  private $crypt;

  function __construct($pdo = null) {
    
    if (empty($this->tableName)) {
      $this->tableName = strtolower($this->className);
    }
    $this->pdoInstance = $pdo;
    $thisClass = new \ReflectionClass($this->className);
    
    // $this->modelProperties = array_map(function($prop) {
      // return $prop->name;
    // }, array_filter(
      // $thisClass->getProperties(\ReflectionProperty::IS_PUBLIC),
      // function($prop) use ($thisClass) {
        // return $prop->getDeclaringClass()->getName() == $thisClass->getName();
      // }
    // ));
    
    // The properties of the model we will map. Have to be public!
    $this->modelProperties = array_map(function($prop) {
      return $prop->name;
    }, $thisClass->getProperties(\ReflectionProperty::IS_PUBLIC));
    
    $this->tableProperties = array_diff(
        $this->modelProperties, $this->foreignProperties);
        
    if (empty($this->selectQuery)) {
      $this->selectQuery = 'SELECT ' . implode(', ', $this->tableProperties)
          . ' FROM ' . $this->tableName;
    }
  }
  
  public static function setConnectionParameters($host, $user, $pwd, $db) {
    self::$staticPdo = array(
      'host' => $host,
      'user' => $user,
      'db' => $db,
      'pwd' => $pwd,
      'pdo' => null
    );
  }
  
  protected function setCrypt($crypt) {
    $this->crypt = $crypt;
  }
  
  private static function getOrOpenPDO() {
    if (self::$staticPdo['pdo'] === null) {
      $pdo = self::$staticPdo;
      $con = new \PDO('mysql:host=' . $pdo['host'] . ';dbname='
        . $pdo['db']
        . ';charset=utf8', $pdo['user'], $pdo['pwd']);
      $con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
      self::$staticPdo['pdo'] = $con;
    }
    return self::$staticPdo['pdo'];
  }
  
  private function execute($query, $params = null) {
    $pdo = $this->getCurrentPDO();
    $st = $pdo->prepare($query);
    $result = $st->execute($params);
    if ($result === false) {
      return false;
    }
    return $st;
  }
  
  private function map($row) {
    $className = $this->className;
    $item = new $className();
    foreach ($this->modelProperties as $prop) {
      if (array_key_exists($prop, $row)) {
        if ($row[$prop] !== '' && $row[$prop] !== null
            && in_array($prop, $this->encryptedProperties)) {
          $item->{$prop} = $this->crypt->decrypt($row[$prop]);
        } else {
          $item->{$prop} = $row[$prop];
        }
      }
    }
    return $item;
  }
  
  protected function getCurrentPDO() {
    return !empty($this->instancePdo)
      ? $this->instancePdo : self::getOrOpenPDO();
  }
  
  public function getCollection($query, $params = null, $key = null) {
    $items = array();
    $st = $this->execute($query, $params);
    while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
      if ($key !== null) {
        $items[$row[$key]] = $this->map($row);
      } else {
        $items[] = $this->map($row);
      }
    }
    return $items;
  }

  public function getOne($query, $params = null) {
    $items = $this->getCollection($query, $params);
    if (!empty($items)) {
      return $items[0];
    }
    return null;
  }

  // Filtert de properties uit de input zodat alleen de echte
  // properties overblijven. Deze kun je dus inserten in de tabel.
  private function getFilteredProperties($input) {
    $filtered = array();
    foreach ($input as $key => $value) {
      if (in_array($key, $this->tableProperties)) {
        $filtered[$key] = $value;
      }
    }
    return $filtered;
  }

  private function fillParams($properties, &$sqlSetOrWhere, &$sqlParams) {
    $className = $this->className;
    foreach ($properties as $key => $value) {
      if ($value !== '' && $value !== null
          && in_array($key, $this->encryptedProperties)) {
        $sqlParams[":$key"] = $this->crypt->encrypt($value);
      } else {
        $sqlParams[":$key"] = $value;
      }
      $sqlSetOrWhere[] = "$key = :$key";
    }
  }

  public function create($properties, $rawProperties = null) {
    $filtered = $this->getFilteredProperties($properties);
    $sqlSet = array();
    $sqlParams = array();
    $this->fillParams($filtered, $sqlSet, $sqlParams);
    $query = "insert into " . $this->tableName
      . " set " . implode(', ', $sqlSet);
    if (!empty($rawProperties)) {
      $filteredRaw = $this->getFilteredProperties($rawProperties);
      $sqlSetRaw = array();
      foreach ($filteredRaw as $key => $value) {
        $sqlSetRaw[] = "$key = $value";
      }
      $query .= ", " . implode(', ', $sqlSetRaw);
    }
    $this->execute($query, $sqlParams);
    return $this->getCurrentPDO()->lastInsertId();
  }

  public function update($where, $properties, $rawProperties = null) {
    
    $sqlParams = array();
    $sqlSet = array();
    $sqlWhere = array();
    
    $this->fillParams($this->getFilteredProperties($properties),
        $sqlSet, $sqlParams);    
    $this->fillParams($this->getFilteredProperties($where),
        $sqlWhere, $sqlParams);
    
    $query = "update " . $this->tableName
      . " set " . implode(', ', $sqlSet);
    if (!empty($rawProperties)) {
      $filteredRaw = $this->getFilteredProperties($rawProperties);
      $sqlSetRaw = array();
      foreach ($filteredRaw as $key => $value) {
        $sqlSetRaw[] = "$key = $value";
      }
      $query .= (empty($properties) ? " " : ", ") . implode(', ', $sqlSetRaw);
    }
    $query .= " where " . implode(' AND ', $sqlWhere);
    return $this->execute($query, $sqlParams);
  }

  public function delete($where) {
    $filtered = $this->getFilteredProperties($where);
    $sqlWhere = array();
    $sqlParams = array();
    $this->fillParams($filtered, $sqlWhere, $sqlParams);
    $query = "delete from " . $this->tableName
      . " where " . implode(' AND ', $sqlWhere);
    return $this->execute($query, $sqlParams);
  }
  
}

// Als je setEncryption aanroepty, dan moet dt object de PDOCrypt
// interface implementeren.
interface PDOCrypt {
  public function encrypt($s);
  public function decrypt($s);
}



?>
