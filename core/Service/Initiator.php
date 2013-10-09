<?php
/**
 * @file: Initiator.php
 * Interface for any class which will dispatch a request to a service (initiate a response).
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Service.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Request.php')));

interface AblePolecat_Service_InitiatorInterface extends AblePolecat_AccessControl_ArticleInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * Prepares a request to be dispatched to a service.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent with access to requested service.
   * @param AblePolecat_Message_RequestAbstract $request The unprepared request.
   *
   * @return AblePolecat_Service_InitiatorInterface Client prepared to dispatch request.
   * @throw AblePolecat_Service_Exception if request could not be prepared.
   */
  public function prepare(AblePolecat_AccessControl_AgentInterface $Agent, 
    AblePolecat_Message_RequestInterface $request);
  
  /**
   * Dispatch a prepared request to a service.
   *
   * @return bool TRUE if the request was dispatched, otherwise FALSE.
   */
  public function dispatch();
}