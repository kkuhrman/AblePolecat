<?php 
/**
 * @file      polecat/Exception/AccessControl.php
 * @brief     Exceptions thrown by Able Polecat Access Control objects.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
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
  
  /**
   * Helper function formats exception message in event of access control violation.
   *
   * @param mixed $Subject Subject attempting to access restricted object.
   * @param mixed $Object Object subject is attempting to access.
   * @param mixed $Authority Access control authority attempting to grant acccess.
   *
   * @throw string formatted message.
   */
  public static function formatDenyAccessMessage (
    AblePolecat_AccessControl_SubjectInterface $Subject = NULL,
    AblePolecat_AccessControl_ArticleInterface $Object = NULL, 
    AblePolecat_AccessControl_SubjectInterface $Authority = NULL) {
    
    $message = sprintf("[%s] identified by '%s' is denied access to [%s] identified by '%s'.",
      isset($Subject) ? $Subject->getName() : 'null',
      isset($Subject) ? $Subject->getId() : 'null',
      isset($Object) ? $Object->getName() : 'null',
      isset($Object) ? $Object->getId() : 'null'
    );
    if (isset($Authority)) {
      $message .= ' ' . sprintf("[%s] identified by '%s' is not authorized to grant this request.",
        $Authority->getName(),
        $Authority->getId()
      );
    }
    return $message;
  }
}