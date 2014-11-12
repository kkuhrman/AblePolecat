<?php
/**
 * @file      polecat/core/Log/Pdo.php
 * @brief     Logs messages to application database.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Log.php');

class AblePolecat_Log_Pdo extends AblePolecat_LogAbstract {
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Log_Pdo or NULL.
   * @throw AblePolecat_Log_Exception if PDO database is not accessible.
   * @see initialize().
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {   
    $Log = new AblePolecat_Log_Pdo($Subject);
    return $Log;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_LogInterface.
   ********************************************************************************/
  
  /**
   * Helper function.Writes message to file.
   * 
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   */
  public function putMessage($type, $msg) {
    !is_string($msg) ? $message = serialize($msg) : $message = $msg;
    switch ($type) {
      default:
        $type = 'info';
        break;
      case AblePolecat_LogInterface::STATUS:
      case AblePolecat_LogInterface::WARNING:
      case AblePolecat_LogInterface::ERROR:
      case AblePolecat_LogInterface::DEBUG:
        break;
    }
    $sql = __SQL()->
      insert('userId', 'eventSeverity', 'eventMessage')->
      into('log')->
      values(1, $type, $message);
    $Result = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    if(!$Result->success()) {
      //
      // @todo: what if log to DB fails?
      //
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
    
  /**
   * Extends __construct().
   */
  protected function initialize() {
  }
}