<?php
/**
 * @file      polecat/core/Data/Primitive/StdObject.php
 * @brief     Encapsulates PHP stdClass.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Structure.php')));

interface AblePolecat_Data_Primitive_StdObjectInterface 
  extends AblePolecat_Data_StructureInterface, 
          AblePolecat_Data_PrimitiveInterface {
}

class AblePolecat_Data_Primitive_StdObject 
  extends AblePolecat_Data_StructureAbstract 
  implements AblePolecat_Data_Primitive_StdObjectInterface {
  
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
    // Create parent element.
    //
    !isset($Document) ? $Document = new DOMDocument() : NULL;
    $Element = $Document->createElement(AblePolecat_Data::getDataTypeName($this));
    
    //
    // Iterate through properties and create child elements.
    //
    $Property = $this->getFirstProperty();
    while ($Property) {
      $propertyName = $this->getPropertyKey();
      $Child = $Property->getDomNode($Document);
      $Element->appendChild($Child);
      $Property = $this->getNextProperty();
    }
    return $Element;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Data_PrimitiveInterface.
   ********************************************************************************/
   
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
      $Data = new AblePolecat_Data_Primitive_StdObject();
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
    return 'StdObject';
  }
}