<?php
/**
 * @file: Browser.php
 * Formats messages as XHTML and outputs to web browser.
 * Generally used for interactive sessions, debugging and fail conditions.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Log.php');

class AblePolecat_Log_Browser extends AblePolecat_LogAbstract {
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
  }
  
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
    echo sprintf("<p>Able Polecat %s %s</p>\n", $type, $message);
  }
  
  /**
   * Dump backtrace to logger with message.
   *
   * Typically only called in a 'panic' situation during testing or development.
   *
   * @param variable $msg Variable list of arguments comprising message.
   */
  public static function dumpBacktrace($msg = NULL) {
    $debug_backtrace = debug_backtrace();
    $Log = new AblePolecat_Log_Browser();
    $headmsg = '';
    isset($msg) ? $headmsg .= "$msg<br /><br />" : NULL;
    $headmsg .= "dumping backtrace...<br />";
    $Log->putMessage(AblePolecat_LogInterface::DEBUG, $headmsg);
    foreach($debug_backtrace as $line => $trace) {
      $Log->putMessage(AblePolecat_LogInterface::DEBUG, print_r($trace, TRUE));
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
   * @return AblePolecat_Log_Browser or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    return new AblePolecat_Log_Browser();
  }
}
