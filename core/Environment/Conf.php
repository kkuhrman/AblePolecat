<?php
/**
 * @file: Conf.php
 * Base class for Environment which uses a conf file to store some settings.
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');
require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf.php');

abstract class AblePolecat_Environment_ConfAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_EnvironmentInterface {
  
  /**
   * @var Environment configuration data.
   */
  protected $Config;
  
  /**
   * Extends __construct(). 
   * 
   * Sub-classes can override to initialize members prior to wakeup().
   */
  protected function initialize() {
    $this->Config = NULL;
  }
  
  /**
   * Return access control agent.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent() {
    return AblePolecat_AccessControl::wakeup()->getAgent($this);
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
    if (isset($this->Config)) {
      $Conf = $this->Config->read($this->getAgent(), $start, $end);
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
    if (isset($this->Config)) {
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
    if ($Config->open($this->getAgent(), $Url)) {
      $this->Config = $Config;
    }
  }
}