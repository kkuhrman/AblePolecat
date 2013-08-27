<?php
/**
 * Public interface to Able Polecat Logger.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

if (isset($_SERVER['WINDIR'])) {
  define('ABLE_POLECAT_EVENT_ID_ERROR',       LOG_ERR);
  define('ABLE_POLECAT_EVENT_ID_WARNING',     LOG_WARNING);
  define('ABLE_POLECAT_EVENT_ID_INFORMATION', LOG_INFO);
  define('ABLE_POLECAT_EVENT_ID_DEBUG',       LOG_DEBUG);
}
else {
  define('ABLE_POLECAT_EVENT_ID_ERROR',       LOG_ERR);
  define('ABLE_POLECAT_EVENT_ID_WARNING',     LOG_WARNING);
  define('ABLE_POLECAT_EVENT_ID_INFORMATION', LOG_INFO);
  define('ABLE_POLECAT_EVENT_ID_DEBUG',       LOG_DEBUG);
}

interface AblePolecat_LogInterface {
  
  const ERROR   = 'error';
  const DEBUG   = 'debug';
  const INFO    = 'info';
  const STATUS  = 'status';
  const WARNING = 'warning';
  
  
  const EVENT_ID_INFO  = ABLE_POLECAT_EVENT_ID_INFORMATION;
  const EVENT_ID_WARN  = ABLE_POLECAT_EVENT_ID_WARNING;
  const EVENT_ID_ERROR = ABLE_POLECAT_EVENT_ID_ERROR;
  const EVENT_ID_DEBUG = ABLE_POLECAT_EVENT_ID_DEBUG;
  
  /**
   * Log a status message to stdout.
   * 
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function logStatusMessage($msg = NULL);
  
  /**
   * Log a status message to stdout.
   * 
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function logWarningMessage($msg = NULL);
  
  /**
   * Log a error message to stderr.
   * 
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function logErrorMessage($msg = NULL);
  
  /**
   * Dump backtrace to logger with message.
   *
   * Typically only called in a 'panic' situation during testing or development.
   *
   * @param variable $msg Variable list of arguments comprising message.
   */
  public static function dumpBacktrace($msg = NULL);
  
  /**
   * Creational function, returns logger ready to write to stdout or stderr.
   *
   * @return object Instance of class implmenting AblePolecat_LogInterface.
   */
  public static function wakeup();
  
  /**
   * Flush any buffers and close down connections to stdout and stderr.
   */
  public function sleep();
}

abstract class AblePolecat_LogAbstract implements AblePolecat_LogInterface {
  
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
  }
  
  /**
   * Sub classes must implement wakeup(), which will return instance of class.
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  final public function __destruct() {
    $this->sleep();
  }
}

/**
 * Exceptions thrown by log objects.
 */
class AblePolecat_Log_Exception extends AblePolecat_Exception {
}