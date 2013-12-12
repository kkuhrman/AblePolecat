<?php
/**
 * @file: Soap.php
 * Base class for SOAP service.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Service.php');

abstract class AblePolecat_Service_SoapAbstract implements AblePolecat_Service_Interface {
  
  /**
   * @var Access control agent.
   */
  private $Agent;
  
  /**
   * @var WSDL path.
   */
  private $wsdl_path;
  
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
   * Set path to WSDL. Should be called in initialize().
   *
   * @param string $wsdl_path
   */
  protected function setWsdlPath($wsdl_path) {
    if (file_exists($wsdl_path) && is_file($wsdl_path)) {
      $this->wsdl_path = $wsdl_path;
    }
    else {
      $this->wsdl_path = NULL;
    }
  }
  
  final protected function __construct() {
    $this->Agent = AblePolecat_Server::getUserMode()->getEnvironment()->getAgent();
    $this->wsdl_path = NULL;
    $this->initialize();
    if (!isset($this->wsdl_path)) {
      throw new AblePolecat_Service_Exception('SOAP service failed to initialize server. No WSDL path provided.',
        AblePolecat_Error::SVC_SERVER_ERROR);
    }
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}