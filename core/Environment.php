<?php
/**
 * @file      polecat/core/Environment.php
 * @brief     Base class for Able Polecat Environment objects.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Environment.php')));

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

abstract class AblePolecat_EnvironmentAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_EnvironmentInterface {
  
  /**
   * @var Environment Variables.
   */
  private $Variables;
  
  /********************************************************************************
   * Implementation of AblePolecat_EnvironmentInterface.
   ********************************************************************************/
  
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
    
    //
    // @todo: access control
    //
    if (isset($this->Variables[$name])) {
      $Value = $this->Variables[$name];
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
    
    //
    // @todo: access control
    //
    $this->Variables[$name] = $value;
    $set = TRUE;

    return $set;
  }
  
  /********************************************************************************
   Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    $this->Variables = array();
  }
}