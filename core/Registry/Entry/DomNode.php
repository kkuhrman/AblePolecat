<?php
/**
 * @file      polecat/core/Registry/Entry/DomNode.php
 * @brief     Base class for registry entry of class encapsulating a DOM Node.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_DomNodeInterface extends AblePolecat_Registry_EntryInterface {  
    
  /**
   * @return string.
   */
  public function getNamespaceUri();
  
  /**
   * @return string.
   */
  public function getQualifiedName();
  
  /**
   * @return string.
   */
  public function getPublicId();
  
  /**
   * @return string.
   */
  public function getSystemId();
    
  /**
   * Serialize doctype properties as Array for storage in db TEXT field.
   *
   * @return string Serialized doc type data.
   */
  public function serializeDocType();
  
  /**
   * Unserialize doctype properties stored as db TEXT field into respective class members.
   *
   * @param string $data Serialized doc type data.
   */
  public function unserializeDocType($data);
}

/**
 * Standard argument list.
 */
abstract class AblePolecat_Registry_Entry_DomNodeAbstract extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_DomNodeInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_DomNode_ResponseInterface.
   ********************************************************************************/
  
  /**
   * @return string.
   */
  public function getNamespaceUri() {
    return $this->getPropertyValue('namespaceUri');
  }
  
  /**
   * @return string.
   */
  public function getQualifiedName() {
    return $this->getPropertyValue('qualifiedName');
  }
  
  /**
   * @return string.
   */
  public function getPublicId() {
    return $this->getPropertyValue('publicId');
  }
  
  /**
   * @return string.
   */
  public function getSystemId() {
    return $this->getPropertyValue('systemId');
  }
    
  /**
   * Serialize doctype properties as Array for storage in db TEXT field.
   *
   * @return string Serialized doc type data.
   */
  public function serializeDocType() {
    $data = array(
      'namespaceUri' => $this->getPropertyValue('namespaceUri'),
      'qualifiedName' => $this->getPropertyValue('qualifiedName'),
      'publicId' => $this->getPropertyValue('publicId'),
      'systemId' => $this->getPropertyValue('systemId'),
    );
    return serialize($data);
  }
  
  /**
   * Unserialize doctype properties stored as db TEXT field into respective class members.
   *
   * @param string $data Serialized doc type data.
   */
  public function unserializeDocType($data) {
    
    $udata = unserialize($data);
    
    if (!isset($udata) || !is_array($udata)) {
      throw new AblePolecat_Data_Exception(sprintf("%s failed. unserialized array expected. %s given.", __METHOD__, AblePolecat_Data::getDataTypeName($udata)));
    }
    
    isset($udata['namespaceUri']) ? $this->namespaceUri = $udata['namespaceUri'] : $this->namespaceUri = '';
    isset($udata['qualifiedName']) ? $this->qualifiedName = $udata['qualifiedName'] : $this->qualifiedName = '';
    isset($udata['publicId']) ? $this->publicId = $udata['publicId'] : $this->publicId = '';
    isset($udata['systemId']) ? $this->systemId = $udata['systemId'] : $this->systemId = '';
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  
  
  /**
   * Assign HTML doc type values to respective doctype properties.
   */
  public function setDocTypeHtml() {
    $this->namespaceUri = AblePolecat_Dom::XHTML_1_1_NAMESPACE_URI;
    $this->qualifiedName = AblePolecat_Dom::XHTML_1_1_QUALIFIED_NAME;
    $this->publicId = AblePolecat_Dom::XHTML_1_1_PUBLIC_ID;
    $this->systemId = AblePolecat_Dom::XHTML_1_1_SYSTEM_ID;
  }
}