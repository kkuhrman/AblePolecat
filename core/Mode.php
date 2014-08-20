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
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Target.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Mode.php')));

interface AblePolecat_ModeInterface extends 
  AblePolecat_CacheObjectInterface, 
  AblePolecat_AccessControl_SubjectInterface, 
  AblePolecat_Command_TargetInterface {
  
  /**
   * Handle access control violations.
   */
  // public static function handleAccessControlViolation();
  
  /**
   * Handle errors triggered by child objects.
   */
  public static function handleError($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL);
  
  /**
   * Handle exceptions thrown by child objects.
   */
  public static function handleException(Exception $Exception);
  
}

abstract class AblePolecat_ModeAbstract extends AblePolecat_Command_TargetAbstract implements AblePolecat_ModeInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_ModeInterface.
   ********************************************************************************/
  
  /**
   * Handle errors triggered by child objects.
   */
  public static function handleError($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL) {
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->handleError($errno, $errstr, $errfile, $errline, $errcontext);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = sprintf("Error [%d] in Able Polecat. %s No command target was able to intercept.",
        $errno, $errstr
      );
      trigger_error($message, E_USER_ERROR);
    }
    
    return $Result;
  }
  
  /**
   * Handle exceptions thrown by child objects.
   */
  public static function handleException(Exception $Exception) {
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->handleException($Exception);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = sprintf("Unhandled exception [%d] in Able Polecat. %s No command target was able to intercept.",
        $Exception->getCode(), 
        $Exception->getMessage()
      );
      throw new AblePolecat_Mode_Exception($message);
    }
    
    return $Result;
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
    if (isset($args[0]) && is_a($args[0], 'AblePolecat_ModeInterface')) {
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