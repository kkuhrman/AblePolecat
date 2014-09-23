<?php
/**
 * @file      polecat/core/Registry/Entry/Cache.php
 * @brief     Encapsulates record of a resource registered in [resource].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_CacheInterface extends AblePolecat_Registry_EntryInterface {  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return string.
   */
  public function getStatusCode();
  
  /**
   * @return string.
   */
  public function getMimeType();
  
  /**
   * @return string.
   */
  public function getCacheData();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Cache extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_CacheInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_Cache();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_CacheInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getResourceId() {
    return $this->getPropertyValue('resourceId');
  }
  
  /**
   * @return string.
   */
  public function getStatusCode() {
    return $this->getPropertyValue('statusCode');
  }
  
  /**
   * @return string.
   */
  public function getMimeType() {
    return $this->getPropertyValue('mimeType');
  }
  
  /**
   * @return string.
   */
  public function getCacheData() {
    return $this->getPropertyValue('cacheData');
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