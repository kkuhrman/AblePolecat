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
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Mode.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Overloadable.php')));

interface AblePolecat_ModeInterface extends 
  AblePolecat_CacheObjectInterface, 
  AblePolecat_Command_TargetInterface,
  AblePolecat_OverloadableInterface {
  
  const ARG_SUBJECT     = 'subject';
  const ARG_INVOKER     = 'commandInvoker';
}

abstract class AblePolecat_ModeAbstract extends AblePolecat_Command_TargetAbstract implements AblePolecat_ModeInterface {
  
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
              $ArgsList->{AblePolecat_ModeInterface::ARG_INVOKER} = $value;
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
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  abstract protected function initialize();
  
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    
    //
    // Process constructor arguments
    //
    $args = func_get_args();
    if (isset($args[1]) && is_a($args[1], 'AblePolecat_Host')) {
      $Host = $args[1];
      //
        // Set chain of responsibility relationship
        //
        $Host->setForwardCommandLink($this);
        $this->setReverseCommandLink($Host);
    }
    
    //
    // Initialize sub-class members.
    //
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}