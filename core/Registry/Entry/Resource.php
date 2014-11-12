<?php
/**
 * @file      polecat/core/Registry/Entry/Resource.php
 * @brief     Encapsulates record of a resource registered in [resource].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ResourceInterface extends AblePolecat_Registry_EntryInterface {
  
  /**
   * @return string
   */
  public function getHostName();
  
  /**
   * @return string.
   */
  public function getResourceName();
  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return string.
   */
  public function getResourceClassName();
  
  /**
   * @return string.
   */
  public function getTransactionClassName();
  
  /**
   * @return string.
   */
  public function getAuthorityClassName();
  
  /**
   * @return int.
   */
  public function getResourceDenyCode();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Resource extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ResourceInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_Resource();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ResourceInterface.
   ********************************************************************************/
  
  /**
   * @return string
   */
  public function getHostName() {
    return $this->getPropertyValue('hostName');
  }
  
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
   * @return string.
   */
  public function getResourceClassName() {
    return $this->getPropertyValue('resourceClassName');
  }
  
  /**
   * @return string.
   */
  public function getTransactionClassName() {
    return $this->getPropertyValue('transactionClassName');
  }
  
  /**
   * @return string.
   */
  public function getAuthorityClassName() {
    return $this->getPropertyValue('authorityClassName');
  }
  
  /**
   * @return int.
   */
  public function getResourceDenyCode() {
    return $this->getPropertyValue('resourceDenyCode');
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