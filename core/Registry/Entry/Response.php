<?php
/**
 * @file      polecat/core/Registry/Entry/Response.php
 * @brief     Encapsulates record of a resource registered in [response].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ResponseInterface extends AblePolecat_Registry_EntryInterface {
  /**
   * @return string.
   */
  public function getResourceName();
  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return int.
   */
  public function getStatusCode();
  
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
   * @return Array.
   */
  public function getDefaultHeaders();
  
  /**
   * @return int.
   */
  public function getResponseClassName();
  
  /**
   * @return string
   */
  public function getTemplateFullPath();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Response extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ResponseInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_Response();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ResponseInterface.
   ********************************************************************************/
  
  /**
   * @return string.
   */
  public function getResourceName() {
    return $this->getPropertyValue('resourceName');
  }
  
  /**
   * @return string.
   */
  public function getResourceId() {
    return $this->getPropertyValue('resourceId');
  }
  
  /**
   * @return int.
   */
  public function getStatusCode() {
    return $this->getPropertyValue('statusCode');
  }
  
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
   * @return string.
   */
  public function getDefaultHeaders() {
    return $this->getPropertyValue('defaultHeaders');
  }
  
  /**
   * @return string.
   */
  public function getResponseClassName() {
    return $this->getPropertyValue('responseClassName');
  }
  
  /**
   * @return string
   */
  public function getTemplateFullPath() {
    return $this->getPropertyValue('templateFullPath');
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
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
   * Assign HTML doc type values to respective doctype properties.
   */
  public function setDocTypeHtml() {
    $this->namespaceUri = AblePolecat_Dom::XHTML_1_1_NAMESPACE_URI;
    $this->qualifiedName = AblePolecat_Dom::XHTML_1_1_QUALIFIED_NAME;
    $this->publicId = AblePolecat_Dom::XHTML_1_1_PUBLIC_ID;
    $this->systemId = AblePolecat_Dom::XHTML_1_1_SYSTEM_ID;
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
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}