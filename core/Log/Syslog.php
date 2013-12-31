<?php
/**
 * @file: Syslog.php
 * Special log file for tracing bootstrap.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Clock.php');
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Log.php');

class AblePolecat_Log_Syslog extends AblePolecat_LogAbstract {
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
  }
  
  /**
   * Helper function. Queue messages.
   * 
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   */
  public function putMessage($type, $msg) {
    
    !is_string($msg) ? $message = serialize($msg) : $message = $msg;
    $priority = ABLE_POLECAT_EVENT_ID_INFORMATION;
    switch ($type) {
      default:
        break;
      case AblePolecat_LogInterface::WARNING:
        $priority = ABLE_POLECAT_EVENT_ID_WARNING;
        break;
      case AblePolecat_LogInterface::ERROR:
        $priority = ABLE_POLECAT_EVENT_ID_ERROR;
        break;
      case AblePolecat_LogInterface::DEBUG:
        $priority = ABLE_POLECAT_EVENT_ID_DEBUG;
        break;
    }
    //
    // Send error information to syslog
    //
    if (openlog("AblePolecat", LOG_PID | LOG_ERR, LOG_USER)) {
      syslog($priority, $msg);
      closelog();
    }
  }
  
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
   * @return AblePolecat_Log_Syslog or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    return new AblePolecat_Log_Syslog();
  }
}