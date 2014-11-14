<?php
/**
 * @file      polecat/core/Data/Scalar.php
 * @brief     Encapsulates scalar data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Data.php');

interface AblePolecat_Data_ScalarInterface extends AblePolecat_DataInterface {
  
  /**
   * @return mixed Encapsulated (scalar or not scalar) data.
   */
  public function getData();
  
  /**
   * @return bool TRUE if data has NULL value, otherwise FALSE.
   */
  public function isNull();
  
  /**
   * Casts the given parameter into an instance of data class.
   *
   * @param mixed $data
   *
   * @return Concrete instance of AblePolecat_Data_ScalarInterface
   * @throw AblePolecat_Data_Exception if type cast is invalid.
   */
  public static function typeCast($data);
  
  /**
   * @return Data expressed as a string.
   */
  public function __toString();
}

abstract class AblePolecat_Data_ScalarAbstract implements AblePolecat_Data_ScalarInterface {
  
  /**
   * @var mixed The value of the encapsulated data.
   */
  private $data;
  
  /********************************************************************************
   * Implementation of Serializable
   ********************************************************************************/
   
  /**
   * @return string serialized representation of AblePolecat_Data_ScalarAbstract.
   */
  public function serialize() {
    return serialize($this->getData());
  }
  
  /**
   * @return concrete instance of AblePolecat_Data_ScalarAbstract.
   */
  public function unserialize($data) {
    $this->setData(unserialize($data));
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_DataInterface
   ********************************************************************************/
  
  /**
   * @return mixed Encapsulated (scalar or not scalar) data.
   */
  public function getData() {
    return $this->data;
  }
  
  /**
   * @return bool TRUE if data has NULL value, otherwise FALSE.
   */
  public function isNull() {
    return isset($this->data);
  }
  
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
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  protected function setData($data) {
    $this->data = $data;
  }
  
  final protected function __construct() {
    $args = func_get_args();
    if (isset($args[0])) {
      $this->data = $args[0];
    }
    else {
      $this->data = NULL;
    }
  }
}