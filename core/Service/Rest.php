<?php
/**
 * @file: Rest.php
 * Base class for services using REST architecture.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Service.php');

abstract class AblePolecat_Service_RestAbstract implements AblePolecat_Service_Interface {
  
  /**
   * @var Access control agent.
   */
  private $Agent;
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  abstract protected function initialize();
  
  /**
   * @return AblePolecat_AccessControl_Agent_User.
   */
  protected function getAgent() {
    return $this->Agent;
  }
  
  /**
   * Process request passed as object by mapping to appropriate class method.
   *
   * @param AblePolecat_Message_RequestInterface $Request Optional.
   *
   * @return AblePolecat_Message_ResponseInterface or NULL.
   * @see handle().
   */
  abstract protected function route(AblePolecat_Message_RequestInterface $Request);
  
  /**
   * @see initialize()
   */
  final protected function __construct() {
    $this->Agent = AblePolecat_Server::getUserMode()->getEnvironment()->getAgent();
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
  
  /**
   * Initiate web service server and process request.
   *
   * @param AblePolecat_Message_RequestInterface $Request Optional.
   *
   * @return AblePolecat_Message_ResponseInterface or NULL.
   */
  public function handle(AblePolecat_Message_RequestInterface $Request = NULL) {
    
    if (!isset($Request)) {
      isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
      switch ($method) {
        default:
          break;
        case 'GET':
          $Request = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Get');
          break;
        case 'POST':
          $Request = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Post');
          break;
        case 'PUT':
          $Request = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Put');
          break;
        case 'DELETE':
          $Request = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Delete');
          break;
      }
      
      //
      // @todo: get request HEAD
      // @todo: get request BODY
      //
    }
    $Response = $this->route($Request);
    
    return $Response;
  }
}