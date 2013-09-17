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
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    self::$ServerMode = NULL;
    
    //
    // Check for required server resources.
    // (these will throw exception if not ready).
    //
    AblePolecat_Server::getBootMode();
    AblePolecat_Server::getClassRegistry();
  }
  
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
    
    //
    // @todo: perhaps better diagnostics.
    // serialize() is not supported for all types
    //
    // isset($errcontext) ? $msg .= ' : ' . serialize($errcontext) : NULL;
    // isset($errcontext) ? $msg .= ' : ' . get_class($errcontext) : NULL;
    
    //
    // Send error information to syslog
    //
    if (openlog("AblePolecat", LOG_PID | LOG_ERR, LOG_USER)) {
      syslog(LOG_ERR, $msg);
      closelog();
    }
    else {
      throw new AblePolecat_Exception(AblePolecat_Error::defaultMessage(AblePolecat_Error::ACCESS_SYSLOG_FAIL),
        AblePolecat_Error::ACCESS_SYSLOG_FAIL);
    }
    if ($die) {
      die("Fatal error in Able Polecat ($errstr)");
    }
    return $die;
  }
  
  /**
   * Used to log exceptions thrown before user logger(s) initialized.
   */
  public static function defaultExceptionHandler($Exception) {
    
    //
    // Send error information to syslog
    //
    if (openlog("AblePolecat", LOG_PID | LOG_ERR, LOG_USER)) {
      //
      // log the exception
      //
      $msg = sprintf("Exception %d thrown in Able Polecat. %s line %d : %s", 
        $Exception->getCode(),
        $Exception->getFile(),
        $Exception->getLine(),
        $Exception->getMessage()
      );
      syslog(LOG_WARNING, "$msg ({$_SERVER['REMOTE_ADDR']},  ({$_SERVER['HTTP_USER_AGENT']}))");
      closelog();
    }
    else {
      //
      // @todo: WTF?
      // @see: and FIX AblePolecat_Server::handleCriticalError()
      //
      throw new AblePolecat_Exception(AblePolecat_Error::defaultMessage(AblePolecat_Error::ACCESS_SYSLOG_FAIL),
        AblePolecat_Error::ACCESS_SYSLOG_FAIL);
    }
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
}