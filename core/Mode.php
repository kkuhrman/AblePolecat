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
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Target.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Mode.php')));

interface AblePolecat_ModeInterface extends 
  AblePolecat_CacheObjectInterface, 
  AblePolecat_AccessControl_SubjectInterface, 
  AblePolecat_Command_TargetInterface {
}

abstract class AblePolecat_ModeAbstract extends AblePolecat_Command_TargetAbstract implements AblePolecat_ModeInterface {
    
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
    if (isset($args[0]) && is_a($args[0], 'AblePolecat_Host')) {
      $this->setReverseCommandLink($args[0]);
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