<?php
/**
 * @file      polecat/core/Log.php
 * @brief     Public interface to Able Polecat Logger.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Log.php')));

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
  
  const APP_INFO    = 'a.info';
  const APP_STATUS  = 'a.status';
  const APP_WARNING = 'a.warning';
  
  const USER_INFO    = 'u.info';
  const USER_STATUS  = 'u.status';
  const USER_WARNING = 'u.warning';
  
  const EVENT_ID_INFO  = ABLE_POLECAT_EVENT_ID_INFORMATION;
  const EVENT_ID_WARN  = ABLE_POLECAT_EVENT_ID_WARNING;
  const EVENT_ID_ERROR = ABLE_POLECAT_EVENT_ID_ERROR;
  const EVENT_ID_DEBUG = ABLE_POLECAT_EVENT_ID_DEBUG;
  
  /**
   * Queue message in event log output buffer.
   * 
   * @param string $type Event severity e.g. error | warning | status.
   * @param string $msg  Body of message.
   */
  public function putMessage($type, $msg);
}

abstract class AblePolecat_LogAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_LogInterface {
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
}
