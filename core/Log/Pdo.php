<?php
/**
 * @file: Pdo.php
 * Logs messages to application database.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Log.php');

class AblePolecat_Log_Pdo extends AblePolecat_LogAbstract {
  
  /**
   * @var resource Handle to log file.
   */
  private $Database;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    //
    // $safe parameter is FALSE to bypass handleCriticalError().
    //
    $this->Database = AblePolecat_Server::getDatabase(FALSE);
    if (!isset($this->Database)) {
      throw new AblePolecat_Log_Exception(
        'Failed to initialize database logger. No connection to application database.',
        AblePolecat_Error::BOOTSTRAP_LOGGER
      );
    }
  }
  
  /**
   * Helper function.Writes message to file.
   * 
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   */
  public function putMessage($type, $msg) {
    if (isset($this->Database)) {
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
      try {
        $sql = __SQL()->
          insert('user_id', 'event_type', 'event_data')->
          into('log')->
          values(1, $type, $message);
        $Stmt = $this->Database->prepareStatement($sql);
        $Stmt->execute();
      }
      catch(Exception $Exception) {
        //
        // @todo: what if log to DB fails?
        //
      }
    }
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
    try {
      $Log = new AblePolecat_Log_Pdo();
      foreach($debug_backtrace as $line => $trace) {
        $Log->putMessage(AblePolecat_LogInterface::DEBUG, print_r($trace, TRUE));
      }
    }
    catch (Exception $Exception) {
      echo $Exception->getMessage();
      foreach($debug_backtrace as $line => $trace) {
        print_r($trace);
      }
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
   * @return AblePolecat_Log_Pdo or NULL.
   * @throw AblePolecat_Log_Exception if PDO database is not accessible.
   * @see initialize().
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {   
    $Log = new AblePolecat_Log_Pdo();
    return $Log;
  }
}