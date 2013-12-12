<?php
/**
 * Base for web service clients.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Initiator.php')));

/**
 * Manages a client connection to a web services provider.
 */
interface AblePolecat_Service_ClientInterface extends AblePolecat_Service_InitiatorInterface {
}

abstract class AblePolecat_Service_ClientAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_Service_ClientInterface {
  
  /**
   * @var object Instance of PHP client.
   */
  protected $Client;
  
  /**
   * @var Prepared requests, ready to dispatch. FIFO.
   */
  private $PreparedRequests;
  
  /**
   * @var bool Internal lock. Prevents concurrent dispatching of requests.
   */
  private $lock;
  
  /**
   * Handle a prepared request.
   *
   * @param mixed $Request.
   *
   * @return mixed Data returned by client in response to request.
   */
  abstract protected function handlePreparedRequest($Request);
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->PreparedRequests = array();
    $this->lock = FALSE;
  }
  
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
    $Response->data = $this->handlePreparedRequest($PreparedRequest);
    
    //
    // Release lock and return response.
    //
    $this->setLock(FALSE);
    return $Response;
  }
}

/**
 * Exceptions thrown by Able Polecat service clients.
 */
class AblePolecat_Service_Client_Exception extends AblePolecat_Exception {
}