<?php 
/**
 * @file      polecat/Exception/AccessControl.php
 * @brief     Exceptions thrown by Able Polecat Access Control objects.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

class AblePolecat_AccessControl_Exception extends AblePolecat_Exception {
  
  /**
   * Subject (or Agent) does not have given permission to access Resource.
   *
   * @param mixed $Resource
   * @param mixed $Constraint
   * @param mixed $Subject
   */
  public static function onViolation($Resource, $Constraint = NULL, $Subject = NULL) {
    
    isset($Subject) ? $msg = ' ' . get_class($Subject) . ' is not permitted to wakeup server mode' : $msg = 'Cannot wakeup server mode.';
    
    throw new AblePolecat_AccessControl_Exception($msg, AblePolecat_Error::ACCESS_DENIED);
  }
}