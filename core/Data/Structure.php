<?php
/**
 * @file      polecat/core/Data/Structure.php
 * @brief     Encapsulates not scalar data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'DynamicObject.php')));

interface AblePolecat_Data_StructureInterface 
  extends AblePolecat_CacheObjectInterface, 
          AblePolecat_DataInterface,
          AblePolecat_StdObjectInterface {
}

abstract class AblePolecat_Data_StructureAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_Data_StructureInterface {
  
  /**
   *  @var public class data members.
   */
  private $properties;
  
  /********************************************************************************
   * Implementation of Serializable
   ********************************************************************************/
   
  /**
   * @return string serialized representation of AblePolecat_Data_ScalarAbstract.
   */
  public function serialize() {
    return serialize($this->properties);
  }
  
  /**
   * @return concrete instance of AblePolecat_Data_ScalarAbstract.
   */
  public function unserialize($data) {
    $this->properties = unserialize($data);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_DataInterface.
   ********************************************************************************/
  
  /**
   * @param DOMDocument $Document.
   * @param string $tagName Name of element tag (default is data type).
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document, $tagName = NULL) {
    
    //
    // Create parent element.
    //
    !isset($tagName) ? $tagName = AblePolecat_Data::getDataTypeName($this) : NULL;
    $Element = $Document->createElement($tagName);
    
    //
    // Iterate through properties and create child elements.
    //
    $Property = $this->getFirstProperty();
    while ($Property) {
      $propertyName = $this->getPropertyKey();
      $Child = $Property->getDomNode($Document, $propertyName);
      $Element->appendChild($Child);
      $Property = $this->getNextProperty();
    }
    return $Element;
    
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
    if (!is_a($value, 'AblePolecat_DataInterface')) {
      $value = AblePolecat_Data_Scalar_String::typeCast($value);
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
   * @return bool TRUE if property given by $name is set,otherwise FALSE.
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
   * Allocates array if not already allocated.
   *
   * It seems intuitively inefficient to step into this function every time.
   * It is a protection against failure to call parent method in subclasses.
   * (for example, subclass overrides initialize()).
   */
  private function checkAlloc() {
    if (!isset($this->properties)) {
      $this->properties = array();
    }
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
    }
    return $property;
  }
  
  /**
   * @return AblePolecat_DataInterface Value of first property in structure or FALSE.
   */
  public function getFirstProperty() {
    $this->checkAlloc();
    $property = reset($this->properties);
    return $property;
  }
  
  /**
   * @return AblePolecat_DataInterface Value of next property in structure or FALSE.
   */
  public function getNextProperty() {
    $this->checkAlloc();
    $property = next($this->properties);
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
   * Extends __construct().
   *
   * Sub-classes can override to initialize extended data members.
   */
  protected function initialize() {
  }
}