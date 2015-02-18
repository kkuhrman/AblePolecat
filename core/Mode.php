<?php
/**
 * @file      polecat/core/Mode.php
 * @brief     A link in the command processing chain of responsibility.
 *
 * Able Polecat Modes are similar to OS protection rings, in terms of access control,
 * but serve also as an implementation of the chain of responsibility (COR) design 
 * pattern, either processing a command or passing it to the next, higher level of 
 * responsibility in the the chain/hierarchy.
 *
 * The simplest COR hierarchy in Able Polecat:
 * Server Mode - Receives HTTP request, sends HTTP response
 * Application Mode - Handles interaction between Able Polecat objects via class methods. 
 * User Mode - Handles interaction between Able Polecat objects and user session.
 *
 * Important responsibilities of the Mode class:
 * 1. Handle errors and exceptions.
 * 2. Encapsulate environment configuration settings.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Chain.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Mode.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Overloadable.php')));

interface AblePolecat_ModeInterface extends 
  AblePolecat_Command_TargetInterface,
  AblePolecat_OverloadableInterface {
  
  const ARG_SUBJECT         = 'subject';
  const ARG_REVERSE_TARGET  = 'reverseTarget';
  
  /**
   * Returns assigned value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   *
   * @return mixed Assigned value of given variable or NULL.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function getEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name);
  
  /**
   * Assign value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   * @param mixed $value Value of variable.
   *
   * @return bool TRUE if variable is set, otherwise FALSE.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function setEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name, $value);
}

abstract class AblePolecat_ModeAbstract 
  extends AblePolecat_Command_TargetAbstract 
  implements AblePolecat_ModeInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_OverloadableInterface.
   ********************************************************************************/
  
  /**
   * Marshall numeric-indexed array of variable method arguments.
   *
   * @param string $method_name __METHOD__ is good enough.
   * @param Array $args Variable list of arguments passed to method (i.e. get_func_args()).
   * @param mixed $options Reserved for future use.
   *
   * @return Array Associative array representing [argument name] => [argument value]
   */
  public static function unmarshallArgsList($method_name, $args, $options = NULL) {
    
    $ArgsList = AblePolecat_ArgsList::create();
    
    foreach($args as $key => $value) {
      switch ($method_name) {
        default:
          break;
        case 'wakeup':
          switch($key) {
            case 0:
              $ArgsList->{AblePolecat_ModeInterface::ARG_SUBJECT} = $value;
              break;
            case 1:
              if (is_object($value) && is_a($value, 'AblePolecat_Command_TargetInterface')) {
                $ArgsList->{AblePolecat_ModeInterface::ARG_REVERSE_TARGET} = $value;
              }
              else {
                $message = sprintf('AblePolecat_ModeInterface::wakeup() argument #s must be type AblePolecat_Command_TargetInterface, %s passed.',
                  AblePolecat_Data::getDataTypeName($value)
                );
                throw new AblePolecat_Mode_Exception($message);
              }
              break;
          }
          break;
      }
    }
    return $ArgsList;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Alias for AblePolecat_CacheObjectAbstract::getDefaultCommandInvoker().
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  protected function getAgent() {
    return $this->getDefaultCommandInvoker();
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    //
    // Access control agent (system agent).
    //
    $this->setDefaultCommandInvoker(AblePolecat_AccessControl_Agent_System::wakeup());
  }
}