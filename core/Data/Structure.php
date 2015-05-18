<?php
/**
 * @file      polecat/core/Data/Structure.php
 * @brief     Encapsulates not scalar data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'DynamicObject.php')));

interface AblePolecat_Data_StructureInterface 
  extends AblePolecat_StdObjectInterface,
          Serializable {
  /**
   * Returns assigned or default value and will not trigger an error.
   * 
   * @param string $name Name of property.
   * @param mixed $default Default value to return if not assigned.
   *
   * @return mixed Assigned value of property given by $name if assigned, otherwise $default.
   */
  public function getPropertySafe($name, $default = NULL);
  
  /**
   * @return AblePolecat_Data_PrimitiveInterface Value of first property in structure or FALSE.
   */
  public function getFirstProperty();
  
  /**
   * @return AblePolecat_Data_PrimitiveInterface Value of next property in structure or FALSE.
   */
  public function getNextProperty();
  
  /**
   * @return mixed Name of property currently being pointed to.
   */
  public function getPropertyKey();
}

abstract class AblePolecat_Data_StructureAbstract implements AblePolecat_Data_StructureInterface {
  
  /**
   *  @var public class data members.
   */
  private $properties;
  
  /**
   * @var Iterator pointer provides support for implementation of Traversable.
   */
  private $ptrIterator;
  
  /********************************************************************************
   * Implementation of Serializable
   ********************************************************************************/
   
  /**
   * @return string serialized representation of AblePolecat_Data_Primitive_ScalarAbstract.
   */
  public function serialize() {
    return serialize($this->properties);
  }
  
  /**
   * @return concrete instance of AblePolecat_Data_Primitive_ScalarAbstract.
   */
  public function unserialize($data) {
    $this->properties = unserialize($data);
  }
  
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
    $this->checkAlloc();
    if (!is_a($value, 'AblePolecat_Data_PrimitiveInterface')) {
      try {
        $value = AblePolecat_Data::castPrimitiveType($value);
      }
      catch(AblePolecat_Data_Exception $Exception) {
        throw new AblePolecat_Data_Exception(sprintf("Failed to set data structure property %s (%s). %s", 
          $name, 
          AblePolecat_Data::getDataTypeName($value),
          $Exception->getMessage())
        );
      }
    }
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
    $property = NULL;
    $this->checkAlloc();
    if (isset($this->properties[$name])) {
      $property = $this->properties[$name];
    }
    else {
      trigger_error('Undefined property via __get(): ' . $name, E_USER_NOTICE);
    }
    return $property;
  }
  
  /**
   * PHP magic method is triggered by calling isset() or empty() on inaccessible properties.
   *
   * @param string $name  Name of given property.
   *
   * @return bool TRUE if property given by $name is set, otherwise FALSE.
   */
  public function __isset($name) {
    $this->checkAlloc();
    return isset($this->properties[$name]);
  }
  
  /**
   * PHP magic method is invoked when unset() is used on inaccessible properties.
   */
  public function __unset($name) {
    $this->checkAlloc();
    unset($this->properties[$name]);
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
    $this->checkAlloc();
    
    if (isset($this->properties[$name])) {
      $property = $this->properties[$name];
      $this->ptrIterator = $property;
    }
    return $property;
  }
  
  /**
   * @return AblePolecat_Data_PrimitiveInterface Value of first property in structure or FALSE.
   */
  public function getFirstProperty() {
    $this->checkAlloc();
    $property = reset($this->properties);
    $this->ptrIterator = $property;
    return $property;
  }
  
  /**
   * @return AblePolecat_Data_PrimitiveInterface Value of next property in structure or FALSE.
   */
  public function getNextProperty() {
    $this->checkAlloc();
    $property = next($this->properties);
    $this->ptrIterator = $property;
    return $property;
  }
  
  /**
   * @return mixed Name of property currently being pointed to.
   */
  public function getPropertyKey() {
    $this->checkAlloc();
    $property = key($this->properties);
    return $property;
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
      $this->ptrIterator = $property;
    }
    return $property;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return int Number of properties.
   */
  public function size() {
    $this->checkAlloc();
    return count($this->properties);
  }
  
  /**
   * @return mixed Value of internal iterator.
   */
  protected function getIteratorPtr() {
    return $this->ptrIterator;
  }
  
  /**
   * Allocates array if not already allocated.
   *
   * It seems intuitively inefficient to step into this function every time.
   * It is a protection against failure to call parent method in subclasses.
   * (for example, subclass overrides initialize()).
   */
  private function checkAlloc() {
    if (!isset($this->properties)) {
      $this->properties = array();
      $this->ptrIterator = reset($this->properties);
    }
  }
}