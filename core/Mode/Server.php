<?php
/**
 * @file: Server.php
 * Base class for Server modes (most protected).
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Mode.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Environment', 'Server.php')));

abstract class AblePolecat_Mode_ServerAbstract extends AblePolecat_ModeAbstract {
  
  /**
   * @var AblePolecat_Mode_ServerAbstract Concrete ServerMode instance.
   */
  protected static $ServerMode;
  
  /**
   * @var bool Prevents some code from exceuting prior to start().
   */
  protected static $ready = FALSE;
  
  /**
   * Used to handle errors encountered while running in production mode.
   */
  public static function defaultErrorHandler($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL) {
    
    $die = (($errno == E_ERROR) || ($errno == E_USER_ERROR));
    
    //
    // Get error information
    //
    $msg = sprintf("Error in Able Polecat. %d %s", $errno, $errstr);
    isset($errfile) ? $msg .= " in $errfile" : NULL;
    isset($errline) ? $msg .= " line $errline" : NULL;
    isset($errcontext) ? $msg .= ' : ' . serialize($errcontext) : NULL;
    
    //
    // Send error information to syslog
    //
    openlog("AblePolecat", LOG_PID | LOG_ERR, LOG_USER);
    syslog(LOG_ERR, $msg);
    closelog();
    
    if ($die) {
      die('arrrgghh...');
    }
    
    return $die;
  }
  
  /**
   * Used to log exceptions thrown before user logger(s) initialized.
   */
  public static function defaultExceptionHandler($Exception) {
    //
    // open syslog, include the process ID and also send
    // the log to standard error, and user a user defined
    // logging mechanism
    //
    openlog("AblePolecat", LOG_PID | LOG_ERR, LOG_USER);

    //
    // log the exception
    //
    $access = date("Y/m/d H:i:s");
    $message = $Exception->getMessage();
    syslog(LOG_WARNING, "Able Polecat, $access, {$_SERVER['REMOTE_ADDR']},  ({$_SERVER['HTTP_USER_AGENT']}), $message");
    closelog();
  }
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    self::$ServerMode = NULL;
  }
  
  /**
   * Similar to DOM ready() but for Able Polecat server mode.
   *
   * @return AblePolecat_Mode_ServerAbstract or FALSE.
   */
  public static function ready() {
    $ready = self::$ready;
    if ($ready) {
      $ready = self::$ServerMode;
    }
    return $ready;
  }
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep() {
  }
}