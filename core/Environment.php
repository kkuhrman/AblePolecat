<?php
/**
 * @file: Environment.php
 * Public interface to Able Polecat Environment.
 *
 * Duties of the Environment object:
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Mode' . DIRECTORY_SEPARATOR . 'Server.php');
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf.php');

interface AblePolecat_EnvironmentInterface {
  
  /**
   * Initialize the environment for Able Polecat.
   *
   * @return AblePolecat_EnvironmentAbstract sub class.
   */
  public static function load();
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep();
}

abstract class AblePolecat_EnvironmentAbstract implements AblePolecat_EnvironmentInterface {
  
  /**
   * @var Access control agent for Server.
   */
  protected $Agent;
  
  /**
   * @var Environment configuration data.
   */
  protected $Config;
  
  /**
   * Extends __construct(). 
   * 
   * Sub-classes can override to initialize members prior to load.
   */
  protected function initialize() {
    $this->Agent = NULL;
    $this->Config = NULL;
  }
  
  /**
   * Load an agent with access to mode configuration file.
   *
   * @param string $agentClassName Class name of respective access control agent.
   * @param string $agentClassPath Full path to respective agent class file.
   * @param string $createMethod Name of creational method.
   */
  protected function loadAccessControlAgent($agentClassName, $agentClassPath, $createMethod) {
    //
    // Agent must implement AblePolecat_AccessControl_AgentInterface.
    // This should have access to mode config file, which must implement 
    // AblePolecat_AccessControl_ResourceInterface.
    //
    AblePolecat_Server::getClassRegistry()->registerLoadableClass(
      $agentClassName, 
      $agentClassPath,
      $createMethod
    );
    $Agent = AblePolecat_Server::getClassRegistry()->loadClass($agentClassName);
    if (isset($Agent) && is_a($Agent, 'AblePolecat_AccessControl_AgentInterface')) {
      $this->Agent = $Agent;
    }
    else {
      AblePolecat_Server::handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOTSTRAP_AGENT);
    }
    return $this->Agent;
  }
  
  /**
   * Adds given access control agent to environment.
   *
   * @param object Instance of class which implements AblePolecat_AccessControl_AgentInterface.
   */
  public function setAgent(AblePolecat_AccessControl_AgentInterface $Agent) {
    $this->Agent = $Agent;
  }
  
  /**
   * Return access control agent.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent() {
    return $this->Agent;
  }
  
  /**
   * Get environment configuration settings as assoc array.
   *
   * @param string $start Optional offset to start reading from.
   * @param string $end Optional offset to end reading at.
   *
   * @return SimpleXMLElement Environment configuration settings.
   */
  public function getConf($start = NULL, $end = NULL) {
    
    $Conf = array();
    if (isset($this->Agent) && isset($this->Config)) {
      $Conf = $this->Config->read($this->Agent, $start, $end);
    }
    return $Conf;
  }
  
  /**
   * Get environment configuration settings as a resource (file).
   *
   * @return AblePolecat_ConfAbstract.
   */
  public function getConfResource() {
    $Conf = NULL;
    if (isset($this->Agent) && isset($this->Config)) {
      $Conf = $this->Config;
    }
    return $Conf;
  }
    
  /**
   * Set environment configuration data.
   *
   * @param AblePolecat_ConfAbstract $Config
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Config file path.
   */
  public function setConf(AblePolecat_ConfAbstract $Config, AblePolecat_AccessControl_Resource_LocaterInterface $Url) {
    
    //
    // Application configuration file
    //
    if (isset($this->Agent)) {
      if ($Config->open($this->Agent, $Url)) {
        $this->Config = $Config;
      }
    }
  }
  
  /**
   * Sub classes must implement load(), which will return instance of class.
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  final public function __destruct() {
    $this->sleep();
  }
}

/**
 * Exceptions thrown by environment objects.
 */
class AblePolecat_Environment_Exception extends AblePolecat_Exception {
}

