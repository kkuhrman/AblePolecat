<?php
/**
 * @file      polecat/core/Service/Initiator.php
 * @brief     Interface for any class which will dispatch a request to a service (initiate a response).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Service.php')));

interface AblePolecat_Service_InitiatorInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * @return AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource or NULL.
   */
  public function getLocater();
}

/**
 * Base class for service initiators (respond to request, initiate service, return response).
 */
abstract class AblePolecat_Service_InitiatorAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_Service_InitiatorInterface {
  /**
   * @var AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource if any.
   */
  private $Locater;
  
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
   * Implementation of AblePolecat_Service_InitiatorInterface.
   ********************************************************************************/
   
  /**
   * @return AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource or NULL.
   */
  public function getLocater() {
    return $this->Locater;
  }
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Sets URL used to open resource.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Locater.
   */
  protected function setLocater(AblePolecat_AccessControl_Resource_LocaterInterface $Locater) {
    $this->Locater = $Locater;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->Locater = NULL;
  }
}