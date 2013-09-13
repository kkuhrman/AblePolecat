<?php
/**
 * Base for web service clients.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Service', 'Initiator.php')));

/**
 * Manages a client connection to a web services provider.
 */
interface AblePolecat_Service_ClientInterface extends AblePolecat_AccessControl_ArticleInterface, AblePolecat_Service_Interface {
}

abstract class AblePolecat_Service_ClientAbstract implements AblePolecat_Service_ClientInterface {
  
  /**
   * @var object Instance of PHP client.
   */
  protected $Client;
  
  /**
   * Iniitialize client configuration settings prior to attempting a connection.
   *
   * @throw AblePolecat_Service_Client_Exception If client is not ready to connect.
   */
  abstract protected function initialize();
  
  final protected function __construct() {
    $this->Client = NULL;
    $this->initialize();
  }
}

/**
 * Exceptions thrown by Able Polecat service clients.
 */
class AblePolecat_Service_Client_Exception extends AblePolecat_Exception {
}