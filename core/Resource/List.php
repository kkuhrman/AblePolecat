<?php
/**
 * @file      polecat/core/Resource/List.php
 * @brief     Base class for resource encapsulating a list with iterator.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive', 'Array.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource.php')));

interface AblePolecat_Resource_ListInterface
  extends AblePolecat_ResourceInterface,
          AblePolecat_Data_Primitive_ArrayInterface {
}

abstract class AblePolecat_Resource_ListAbstract 
  extends AblePolecat_ResourceAbstract {
  
  /********************************************************************************
   * Implementation of ArrayAccess.
   ********************************************************************************/
   
  /**
   * This method is executed when using isset() or empty() on objects implementing ArrayAccess. 
   *
   * @param mixed $offset An offset to check for.
   * 
   * @return bool TRUE if offset exists, otherwise FALSE.
   */
  public function offsetExists($offset) {
    return $this->__isset($offset);
  }
  
  /**
   * Returns the value at specified offset. 
   *
   * @param mixed $offset The offset to retrieve. 
   *
   * @return AblePolecat_Data_PrimitiveInterface or NULL.
   */
  public function offsetGet($offset) {
    return $this->__get($offset);
  }
  
  /**
   * Assigns a value to the specified offset. 
   *
   * @param mixed $offset The offset to assign the value to. 
   * @param mixed $value  The value to set. 
   */
  public function offsetSet($offset, $value) {
    $this->__set($offset, $value);
  }
  
  /**
   * Unsets an offset. 
   *
   * @param mixed $offset The offset to unset. 
   */
  public function offsetUnset($offset) {
    $this->__unset($offset);
  }
  
  /********************************************************************************
   * Implementation of Iterator.
   ********************************************************************************/
  
  /**
   * Returns the current element.
   *
   * * @return AblePolecat_Data_PrimitiveInterface or NULL.
   */
  public function current() {
    return $this->getIteratorPtr();
  }
  
  /**
   * Returns the key of the current element.
   *
   * @return mixed.
   */
  public function key() {
    $key = NULL;
    if ($this->getIteratorPtr()) {
      $key = $this->getPropertyKey();
    }
    return $key;
  }
  
  /**
   * Move forward to next element.
   */
  public function next() {
    $this->getNextProperty();
  }
  
  /**
   * Rewinds back to the first element of the Iterator. 
   *
   */
  public function rewind() {
    $this->getFirstProperty();
  }
  
  /**
   * Checks if current position is valid.
   *
   * @return bool Returns TRUE on success or FALSE on failure. 
   */
  public function valid() {
    return (bool)$this->getIteratorPtr();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Data_PrimitiveInterface.
   ********************************************************************************/
  
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL) {
    //
    // Create root element.
    //
    !isset($Document) ? $Document = new DOMDocument() : NULL;
    $rootElement = $Document->createElement(AblePolecat_Data::getDataTypeName($this));
    $rootElement->setAttribute('id', $this->getId());
    $rootElement->setIdAttribute('id', true);
    $rootElement->setAttribute('name', $this->getName());
    $rootElement->setAttribute('uri', $this->getUri());
    
    //
    // Create list container.
    //
    $listElement = $Document->createElement('Items');
    
    //
    // Iterate through properties and create child elements.
    //
    $Property = $this->getFirstProperty();
    while ($Property) {
      $propertyName = $this->getPropertyKey();
      $Child = $Property->getDomNode($Document);
      $listElement->appendChild($Child);
      $Property = $this->getNextProperty();
    }
    $rootElement->appendChild($listElement);
    return $rootElement;
  }
  
  /**
   * Casts the given parameter into an instance of data class.
   *
   * @param mixed $data
   *
   * @return Concrete instance of AblePolecat_Data_PrimitiveInterface
   * @throw AblePolecat_Data_Exception if type cast is invalid.
   */
  public static function typeCast($data) {
    
    $Data = NULL;
    
    is_object($data) ? $data = get_object_vars($data) : NULL;
    if (is_array($data)) {
      $Data = new AblePolecat_Data_Primitive_Array();
      foreach($data as $offset => $value) {
        $Data->__set($offset, $value);
      }
    }
    else {
      throw new AblePolecat_Data_Exception(
        sprintf("Cannot cast %s as %s.", AblePolecat_Data::getDataTypeName($data), __CLASS__), 
        AblePolecat_Error::INVALID_TYPE_CAST
      );
    }
    
    return $Data;
  }
  
  /**
   * @return string Data expressed as a string.
   */
  public function __toString() {
    $str = '';
    $tokens = array();
    foreach($this as $key => $value) {
      $tokens[] = sprintf("%s => [%s]", $key, $value->__toString());
    }
    $str = implode(',', $tokens);
    return $str;
  }
}