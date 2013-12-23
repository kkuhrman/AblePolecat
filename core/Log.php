<?php
/**
 * Public interface to Able Polecat Logger.
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));

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

interface AblePolecat_LogInterface extends AblePolecat_CacheObjectInterface {
  
  const BOOT    = 'boot'; // bootstrap info message - not logged unless a problem during boot
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
}

abstract class AblePolecat_LogAbstract implements AblePolecat_LogInterface {
  
  /**
   * Extends __construct(). 
   */
  abstract protected function initialize();
  
  /**
   * Helper function.Writes message to file.
   * 
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   */
  abstract public function putMessage($type, $msg);
  
  /**
   * Log a status message to file.
   * 
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function logStatusMessage($msg = NULL) {
    $this->putMessage(AblePolecat_LogInterface::STATUS, $msg);
  }
  
  /**
   * Log a status message to file.
   * 
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function logWarningMessage($msg = NULL) {
    $this->putMessage(AblePolecat_LogInterface::WARNING, $msg);
  }
  
  /**
   * Log a error message to stderr.
   * 
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function logErrorMessage($msg = NULL) {
    $this->putMessage(AblePolecat_LogInterface::ERROR, $msg);
  }
  
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}

/**
 * Exceptions thrown by log objects.
 */
class AblePolecat_Log_Exception extends AblePolecat_Exception {
}