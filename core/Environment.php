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
    $this->Agent = array();
    $this->Config = NULL;
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

