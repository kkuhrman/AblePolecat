<?php
/**
 * @file      polecat/core/Data/Scalar.php
 * @brief     Encapsulates scalar data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Data.php');

interface AblePolecat_Data_ScalarInterface extends AblePolecat_DataInterface {
  /**
   * @return Data expressed as a string.
   */
  public function __toString();
}

abstract class AblePolecat_Data_Scalar extends AblePolecat_DataAbstract implements AblePolecat_DataInterface {
  
  /********************************************************************************
   * Implementation of Serializable
   ********************************************************************************/
   
  /**
   * @return string serialized representation of AblePolecat_Data_Scalar.
   */
  public function serialize() {
    return serialize($this->getData());
  }
  
  /**
   * @return concrete instance of AblePolecat_Data_Scalar.
   */
  public function unserialize($data) {
    $this->setData(unserialize($data));
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_DataInterface
   ********************************************************************************/
  
  /**
   * @param DOMDocument $Document.
   * @param string $tagName Name of element tag (default is data type).
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document, $tagName = NULL) {
    
    !isset($tagName) ? $tagName = AblePolecat_Data::getDataTypeName($this) : NULL;
    $Element = $Document->createElement($tagName);
    $cData = $Document->createCDATASection($this->__toString());
    $Element->appendChild($cData);
    return $Element;
  }
}