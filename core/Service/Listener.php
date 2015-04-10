<?php
/**
 * @file      polecat/core/Service/Listener.php
 * @brief     Interface for any class which accepts incoming service request.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Service.php')));

interface AblePolecat_Service_ListenerInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
  /**
   * Handle incoming service request.
   *
   * @return mixed.
   */
  public function handleServiceRequest();
}

/**
 * Base class for service initiators (respond to request, initiate service, return response).
 */
abstract class AblePolecat_Service_ListenerAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_Service_ListenerInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SERVICE';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Service_ListenerInterface.
   ********************************************************************************/
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
  }
}