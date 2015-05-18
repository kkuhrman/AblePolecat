<?php
/**
 * @file      polecat/DynamicObject.php
 * @brief     Interface for objects which use PHP overloading to dynamically create properties and methods.
 *
 * The dynamic object is used in Able Polecat to support function/
 * method overloading in the classic OOP sense by passing a single parameter.
 * Functions/methods can leverage this polymorphic feature by knowing the 
 * named parameters expected and what to do with them or having some other
 * intrinsic behaviour to handle them (e.g. building a column list in a SQL 
 * SELECT statement etc).
 *
 * Two basic implementations of this interface can be implemented, late binding
 * or early binding. The first (late binding) must be initialized by naming 
 * properties and assigning values at runtime like so:
 * 
 *     $Instance->myProperty = $myValue;
 *
 * The second (early binding) allows user to pass a variable argument list to 
 * any method but must define ahead of time how to marshal and unmarshal the 
 * arguments for the given method ahead of time. 
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

interface AblePolecat_StdObjectInterface {
    /**
   * PHP magic method is run when writing data to inaccessible properties.
   *
   * @param string $name  Name of property to set.
   * @param mixed  $value Value to assign to given property.
   */
  public function __set($name, $value);
  
  /**
   * PHP magic method is utilized for reading data from inaccessible properties.
   *
   * @param string $name  Name of property to get.
   *
   * @return mixed Value of given property.
   */
  public function __get($name);
  
  /**
   * PHP magic method is triggered by calling isset() or empty() on inaccessible properties.
   *
   * @param string $name  Name of given property.
   *
   * @return bool TRUE if property given by $name is set,otherwise FALSE.
   */
  public function __isset($name);
  
  /**
   * PHP magic method is invoked when unset() is used on inaccessible properties.
   */
  public function __unset($name);
  
  /**
   * Returns assigned or default value and will not trigger an error.
   * 
   * @param string $name Name of given property.
   * @param mixed $default Default value to return if not assigned.
   *
   * @return mixed Assigned value of property given by $name if set, otherwise $default.
   */
  public function getPropertyValue($name, $default = NULL);
}

interface AblePolecat_DynamicObjectInterface extends AblePolecat_StdObjectInterface {
  
  /**
   * Creational method.
   *
   * @return AblePolecat_DynamicObjectInterface Concrete instance of class.
   */
  public static function create();
}

abstract class AblePolecat_DynamicObjectAbstract implements AblePolecat_DynamicObjectInterface {
  
  /**
   *  @var Similar to attributes in XML.
   */
  private $properties;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * PHP magic method is run when writing data to inaccessible properties.
   *
   * @param string $name  Name of property to set.
   * @param mixed  $value Value to assign to given property.
   */
  public function __set($name, $value) {
    $this->properties[$name] = $value;
  }
  
  /**
   * PHP magic method is utilized for reading data from inaccessible properties.
   *
   * @param string $name  Name of property to get.
   *
   * @return mixed Value of given property.
   */
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
   * PHP magic method is triggered by calling isset() or empty() on inaccessible properties.
   *
   * @param string $name  Name of given property.
   *
   * @return bool TRUE if property given by $name is set,otherwise FALSE.
   */
  public function __isset($name) {
    return isset($this->properties[$name]);
  }
  
  /**
   * PHP magic method is invoked when unset() is used on inaccessible properties.
   */
  public function __unset($name) {
    unset($this->properties[$name]);
  }
  
  /**
   * Returns assigned or default value and will not trigger an error.
   * 
   * @param string $name Name of given property.
   * @param mixed $default Default value to return if not assigned.
   *
   * @return mixed Assigned value of property given by $name if set, otherwise $default.
   */
  public function getPropertyValue($name, $default = NULL) {
    
    $property = $default;
    
    if (isset($this->properties[$name])) {
      $property = $this->properties[$name];
    }
    return $property;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  abstract protected function initialize();
  
  final protected function __construct() {
    $this->properties = array();
    $this->initialize();
  }
}