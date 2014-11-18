<?php
/**
 * @file      polecat/core/Registry/Entry/DomNode/Response.php
 * @brief     Encapsulates record of a resource registered in [response].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode.php')));

interface AblePolecat_Registry_Entry_ResponseInterface extends AblePolecat_Registry_Entry_DomNodeInterface {
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
   * @return Array.
   */
  public function getDefaultHeaders();
  
  /**
   * @return int.
   */
  public function getResponseClassName();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_DomNode_Response extends AblePolecat_Registry_Entry_DomNodeAbstract implements AblePolecat_Registry_Entry_ResponseInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_DomNode_Response();
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
  public function getDefaultHeaders() {
    return $this->getPropertyValue('defaultHeaders');
  }
  
  /**
   * @return string.
   */
  public function getResponseClassName() {
    return $this->getPropertyValue('responseClassName');
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}