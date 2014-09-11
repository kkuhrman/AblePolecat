<?php
/**
 * @file      polecat/core/Service/Initiator.php
 * @brief     Interface for any class which will dispatch a request to a service (initiate a response).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Service.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));

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

/**
 * Base class for service initiators (respond to request, initiate service, return response).
 */
abstract class AblePolecat_Service_InitiatorAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_Service_InitiatorInterface {
  
  /**
   * @var Prepared requests, ready to dispatch. FIFO.
   */
  private $PreparedRequests;
  
  /**
   * @var bool Internal lock. Prevents concurrent dispatching of requests.
   */
  private $lock;
  
  /********************************************************************************
   * Implementation of AblePolecat_Service_InitiatorInterface.
   ********************************************************************************/
  
  /**
   * Dispatch a prepared request to a service.
   *
   * @return bool TRUE if the request was dispatched, otherwise FALSE.
   */
  public function dispatch() {
    
    //
    // Set lock.
    //
    $this->setLock();
    
    //
    // Prepare response.
    //
    $Response = AblePolecat_Message_Response::create(200, 'OK');    
    
    //
    // Handle next prepared request.
    //
    $PreparedRequest = $this->getNextPreparedRequest();
    $this->handlePreparedRequest($PreparedRequest, $Response);
    
    //
    // Release lock and return response.
    //
    $this->setLock(FALSE);
    return $Response;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Handle a prepared request.
   *
   * @param AblePolecat_Message_RequestInterface $Request.
   * @param AblePolecat_Message_ResponseInterface &$Response.
   *
   * @throw AblePolecat_Service_Exception If processing request fails.
   */
  abstract protected function handlePreparedRequest($Request, &$Response);
  
  /**
   * Return next request prepared for dispatch.
   *
   * @return mixed Next request or NULL.
   */
  protected function getNextPreparedRequest() {
    return array_shift($this->PreparedRequests);
  }
  
  /**
   * Pushes a prepared request to the end of the list.
   *
   * @param mixed $Request.
   */
  protected function pushPreparedRequest($Request) {
    return array_push($this->PreparedRequests, $Request);
  }
  
  /**
   * @return bool TRUE if lock on client is set, otherwise FALSE.
   */
  protected function isLocked() {
    return $this->lock;
  }
  
  /**
   * @param bool $lock Prevents concurrent dispatching of requests.
   */
  protected function setLock($lock = TRUE) {
    $this->lock = $lock;
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->PreparedRequests = array();
    $this->lock = FALSE;
  }
}
