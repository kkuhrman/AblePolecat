<?php
/**
 * @file: Environment.php
 * Base class for Able Polecat Environment objects.
 *
 * Duties of the Environment object:
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'CacheObject.php')));

interface AblePolecat_EnvironmentInterface extends AblePolecat_CacheObjectInterface {
  
  /**
   * Return access control agent.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent();
}

/**
 * Exceptions thrown by environment objects.
 */
class AblePolecat_Environment_Exception extends AblePolecat_Exception {
}

