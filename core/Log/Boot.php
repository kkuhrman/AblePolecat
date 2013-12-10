<?php
/**
 * @file: Boot.php
 * Special log file for tracing bootstrap.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Clock.php');
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Log.php');

class AblePolecat_Log_Boot extends AblePolecat_LogAbstract {
  
  /**
   * @var object Instance of Singleton.
   */
  private static $Log;
  
  /**
   * @var AblePolecat_Clock Internal stop watch.
   */
  private $Clock;
  
  /**
   * @var Array Messages to log file (cached until sleep()).
   */
  private $messages;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    //
    // Start stop watch
    //
    $this->Clock = new AblePolecat_Clock();
    $this->Clock->start();
      
    $this->messages = array();
    
    $msg = sprintf("Open boot log file @ %s", date('H:i:s u e', time()));
    $this->putMessage(AblePolecat_LogInterface::STATUS, $msg);
  }
  
  /**
   * Helper function. Queue messages.
   * 
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   */
  public function putMessage($type, $msg) {
    
    $time = $this->Clock->getElapsedTime(AblePolecat_Clock::ELAPSED_TIME_TOTAL_ACTIVE, TRUE);    
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
    $this->messages[] = array(
      'time' => $time,
      'type' => $type,
      'body' => $message,
    );
  }
  
  /**
   * Dump backtrace to logger with message.
   *
   * Typically only called in a 'panic' situation during testing or development.
   *
   * @param variable $msg Variable list of arguments comprising message.
   */
  public static function dumpBacktrace($msg = NULL) {
    //
    // Screw it!
    //
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    //
    // boot.csv always logs the most recent bootstrap() and discards any previous logs.
    //
    if (isset(self::$Log)) {
      $msg = sprintf("Close boot log file @ %s", date('H:i:s u e', time()));
      $this->putMessage(AblePolecat_LogInterface::STATUS, $msg);
      $file_name = AblePolecat_Server_Paths::getFullPath('logs') . DIRECTORY_SEPARATOR . 'boot.csv';
      $flog = @fopen($file_name, 'w');
      if ($flog) {
        $message = array_shift($this->messages);
        while (isset($message)) {
          fputcsv($flog, $message);
          $message = array_shift($this->messages);
        }
      }
      else {
        $flog = NULL;
        $msg = sprintf(
          "Able Polecat attempted to open a CSV log file in the directory given at %s. No such directory exists or it is not writable by web agent.",
          AblePolecat_Server_Paths::getFullPath('logs')
        );
        throw new AblePolecat_Log_Exception(
          $msg,
          AblePolecat_Error::BOOTSTRAP_LOGGER
        );
        // trigger_error($msg, E_USER_ERROR);
      }
      
      if (isset($flog)) {
        fclose($flog);
        $flog = NULL;
      }
      
      //
      // Go out of scope
      //
      self::$Log = NULL;
    }
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Log_Boot or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Log)) {
      self::$Log = new AblePolecat_Log_Boot();
    }
    return self::$Log;
  }
}