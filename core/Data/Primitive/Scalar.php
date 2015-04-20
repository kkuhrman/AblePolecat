<?php
/**
 * @file      polecat/core/Data/Primitive/Scalar.php
 * @brief     Encapsulates scalar data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive.php')));

interface AblePolecat_Data_Primitive_ScalarInterface extends AblePolecat_Data_PrimitiveInterface {  
  /**
   * @return mixed Encapsulated (scalar or not scalar) data.
   */
  public function getData();
  
  /**
   * @return bool TRUE if data has NULL value, otherwise FALSE.
   */
  public function isNull();
}

abstract class AblePolecat_Data_Primitive_ScalarAbstract implements AblePolecat_Data_Primitive_ScalarInterface {
  
  /**
   * @var mixed The value of the encapsulated data.
   */
  private $data;
  
  /********************************************************************************
   * Implementation of Serializable
   ********************************************************************************/
   
  /**
   * @return string serialized representation of AblePolecat_Data_Primitive_ScalarAbstract.
   */
  public function serialize() {
    return serialize($this->getData());
  }
  
  /**
   * @return concrete instance of AblePolecat_Data_Primitive_ScalarAbstract.
   */
  public function unserialize($data) {
    $this->setData(unserialize($data));
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Dom_NodeInterface
   ********************************************************************************/
  
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL) {
    !isset($Document) ? $Document = new DOMDocument() : NULL;
    $Element = $Document->createElement(AblePolecat_Data::getDataTypeName($this));
    $cData = $Document->createCDATASection($this->__toString());
    $Element->appendChild($cData);
    return $Element;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Data_PrimitiveInterface
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