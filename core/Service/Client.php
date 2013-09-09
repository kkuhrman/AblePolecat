<?php
/**
 * Base for web service clients.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Service.php');

/**
 * Manages a client connection to a web services provider.
 */
interface AblePolecat_Service_ClientInterface extends AblePolecat_AccessControl_ArticleInterface, AblePolecat_Service_Interface {
  
  /**
   * Prepares a statement for execution and returns a statement object.
   *
   * @param string $statement A data retrieval/manipulation statement in query language supported by client.
   *
   * @return AblePolecat_QueryLanguage_StatementInterface or NULL.
   */
  public function prepare($statement);
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
