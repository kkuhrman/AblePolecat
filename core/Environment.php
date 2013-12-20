<?php
/**
 * @file: Environment.php
 * Base class for Able Polecat Environment objects.
 *
 * Duties of the Environment object:
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

interface AblePolecat_EnvironmentInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * Returns assigned value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   *
   * @return mixed Assigned value of given variable or NULL.
   */
  public function getVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name);
  
  /**
   * Assign value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   * @param mixed $value Value of variable.
   *
   * @return bool TRUE if variable is set, otherwise FALSE.
   */
  public function setVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name, $value);
}

abstract class AblePolecat_EnvironmentAbstract extends AblePolecat_AccessControl_ResourceAbstract implements AblePolecat_EnvironmentInterface {
  
  /**
   * @var Environment Variables.
   */
  private $Variables;
  
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    $this->Variables = array();
  }
  
  /**
   * Returns assigned value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   *
   * @return mixed Assigned value of given variable or NULL.
   */
  public function getVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name) {
    
    $Value = NULL;
    
    if ($this->hasPermission($Agent, AblePolecat_AccessControl_Constraint_Read::getId())) {
      if (isset($this->Variables[$name])) {
        $Value = $this->Variables[$name];
      }
    }
    return $Value;
  }
  
  /**
   * Assign value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   * @param mixed $value Value of variable.
   *
   * @return bool TRUE if variable is set, otherwise FALSE.
   */
  public function setVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name, $value) {
    
    $set = FALSE;
    
    if ($this->hasPermission($Agent, AblePolecat_AccessControl_Constraint_Write::getId())) {
      $this->Variables[$name] = $value;
      $set = TRUE;
    }
    return $set;
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}

/**
 * Exceptions thrown by environment objects.
 */
class AblePolecat_Environment_Exception extends AblePolecat_Exception {
}