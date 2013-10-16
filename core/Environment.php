<?php
/**
 * @file: Environment.php
 * Base class for Able Polecat Environment objects.
 *
 * Duties of the Environment object:
 */

include_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'CacheObject.php')));
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf.php');

abstract class AblePolecat_EnvironmentAbstract extends AblePolecat_CacheObjectAbstract {
  
  /**
   * @var Access control agent for Server.
   */
  protected $Agent;
  
  /**
   * Extends __construct(). 
   * 
   * Sub-classes can override to initialize members prior to wakeup().
   */
  protected function initialize() {
    $this->Agent = NULL;
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
      AblePolecat_Server::handleCriticalError(AblePolecat_Error::BOOTSTRAP_AGENT);
    }
    return $this->Agent;
  }
  
  /**
   * Return access control agent.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent() {
    return $this->Agent;
  }
}

/**
 * Exceptions thrown by environment objects.
 */
class AblePolecat_Environment_Exception extends AblePolecat_Exception {
}

