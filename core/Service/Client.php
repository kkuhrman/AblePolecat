<?php
/**
 * Base for web service clients.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Service', 'Initiator.php')));

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
   * @var bool Internal lock. Prevents concurrent dispatching of requests.
   */
  private $lock;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->lock = FALSE;
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
}

/**
 * Exceptions thrown by Able Polecat service clients.
 */
class AblePolecat_Service_Client_Exception extends AblePolecat_Exception {
}