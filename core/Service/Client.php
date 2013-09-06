<?php
/**
 * Base for web service clients.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Service.php');
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Message.php');

/**
 * Manages a client connection to a web services provider.
 */
interface AblePolecat_Service_ClientInterface extends AblePolecat_AccessControl_ArticleInterface, AblePolecat_Service_Interface {
  
  /**
   * Close connection and destroy current session variables relating to connection.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   */
  public function close(AblePolecat_AccessControl_AgentInterface $Agent = NULL);
  
  /**
   * Send asynchronous message over client connection.
   *
   * @param AblePolecat_MessageInterface $Message.
   */
  public function dispatch(AblePolecat_MessageInterface $Message);
}

abstract class AblePolecat_Service_ClientAbstract implements AblePolecat_Service_ClientInterface {
  
  /**
   * @var object Instance of PHP client.
   */
  protected $Client;
  
  /**
   * Iniitialize client configuration settings prior to attempting a connection.
   *
   * @return bool TRUE if configuration is valid, otherwise FALSE.
   */
  protected function initialize() {
  }
  
  /**
   * Returns the encapsulated client object.
   */
  public function getNativeClient() {
    return $this->Client;
  }
  
  final protected function __construct() {
    $this->Client = NULL;
    $this->initialize();
  }
}
