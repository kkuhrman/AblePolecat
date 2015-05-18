<?php
/**
 * @file      polecat/core/Resource/Core.php
 * @brief     Interface and base class for core (built-in) resources.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Resource.php');

interface AblePolecat_Resource_CoreInterface extends AblePolecat_ResourceInterface, AblePolecat_Dom_NodeInterface {
}

abstract class AblePolecat_Resource_CoreAbstract 
  extends AblePolecat_ResourceAbstract 
  implements AblePolecat_Resource_CoreInterface {

  /********************************************************************************
   * Implementation of AblePolecat_Dom_NodeInterface.
   ********************************************************************************/
  
  /**
   * @return Data expressed as a string.
   */
  public function __toString() {
    $Document = AblePolecat_Dom::createXmlDocument(AblePolecat_Data::getDataTypeName($this));
    $Node = $this->getDomNode($Document);
    return $Node->C14N();
  }
  
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL) {
    
    $Element = NULL;
    
    //
    // Create a default document if necessary.
    //
    !isset($Document) ? $Document = AblePolecat_Dom::createXmlDocument() : NULL;
    
    //
    // Create root element.
    //
    $Element = $Document->createElement(AblePolecat_Data::getDataTypeName($this));
    $Element = AblePolecat_Dom::appendChildToParent($Element, $Document);
    
    //
    // Add child elements for properties.
    //
    $property = $this->getFirstProperty();
    while($property) {
      $tagName = $this->getPropertyKey();
      $childElement = $Document->createElement($tagName);
      if (is_a($property, 'AblePolecat_Data_Primitive_ScalarInterface')) {
        $cData = $Document->createCDATASection($property->__toString());
        $childElement->appendChild($cData);
      }
      else {
        $childNode = $property->getDomNode($Document);
        $childElement->appendChild($childNode);
      }
      $childElement = AblePolecat_Dom::appendChildToParent($childElement, $Document);
      $property = $this->getNextProperty();
    }
    
    return $Element;
  }
}