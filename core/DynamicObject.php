<?php
/**
 * @file: DynamicObject.php
 * Interface for objects whichuse PHP overloading to dynamically create properties and methods.
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

interface AblePolecat_DynamicObjectInterface {
  
  /**
   * Creational method.
   *
   * @return AblePolecat_DynamicObjectInterface Concrete instance of class.
   */
  public static function create();
  
  /**
   * These PHP magic methods must be implemented.
   */
  public function __set($name, $value);

  public function __get($name);
  
  public function __isset($name);

  public function __unset($name);
}

abstract class AblePolecat_DynamicObjectAbstract implements AblePolecat_DynamicObjectInterface {
  
  /**
   *  @var Similar to attributes in XML.
   */
  private $properties;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  abstract protected function initialize();
  
  public function __set($name, $value) {
    $this->properties[$name] = $value;
  }

  public function __get($name) {
    if (isset($this->properties[$name])) {
      return $this->properties[$name];
    }

    $trace = debug_backtrace();
    trigger_error(
      'Undefined property via __get(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_NOTICE
    );
    return null;
  }
  
  /**
   * Returns assigned or default value and will not trigger an error.
   * 
   * @param string $name Name of property.
   * @param mixed $default Default value to return if not assigned.
   *
   * @return mixed Assigned value of property given by $name if assigned, otherwise $default.
   */
  public function getPropertySafe($name, $default = NULL) {
    
    $property = $default;
    if (isset($this->properties[$name])) {
      $property = $this->properties[$name];
    }
    return $property;
  }

  public function __isset($name) {
    return isset($this->properties[$name]);
  }

  public function __unset($name) {
    unset($this->properties[$name]);
  }
  
  public function getFirstProperty() {
    $property = reset($this->properties);
    return $property;
  }
  
  public function getNextProperty() {
    $property = next($this->properties);
    return $property;
  }
  
  public function getPropertyKey() {
    $property = key($this->properties);
    return $property;
  }
  
  final protected function __construct() {
    $this->properties = array();
    $this->initialize();
  }
}

/**
  * Exceptions thrown by Able Polecat message sub-classes.
  */
class AblePolecat_DynamicObject_Exception extends AblePolecat_Exception {
}