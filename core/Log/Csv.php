<?php
/**
 * @file: Csv.php
 * Default logger for ABle Polecat. Sends messages to comma separated file.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Log.php');

class AblePolecat_Log_Csv extends AblePolecat_LogAbstract {
  
  /**
   * @var resource Handle to log file.
   */
  private $hFile;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    //
    // Default name of log file is YYYY_MM_DD.csv
    //
    $file_name = AblePolecat_Server_Paths::getFullPath('logs') . DIRECTORY_SEPARATOR . date('Y_m_d', time()) . '.csv';
    $this->hFile = fopen($file_name, 'a');
    if ($this->hFile == FALSE) {
      $this->hFile = NULL;
      trigger_error('Able Polecat failed to open default log file.', E_USER_ERROR);
    }
  }
  
  /**
   * Helper function.Writes message to file.
   * 
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   */
  protected function putMessage($type, $msg) {
    if (isset($this->hFile)) {
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
      $line = array(
        $type, 
        date('H:i:s u e', time()),
        $message,
      );
      fputcsv($this->hFile, $line);
    }
  }
  
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
   * Dump backtrace to logger with message.
   *
   * Typically only called in a 'panic' situation during testing or development.
   *
   * @param variable $msg Variable list of arguments comprising message.
   */
  public static function dumpBacktrace($msg = NULL) {
    $this->putMessage(AblePolecat_LogInterface::DEBUG, $msg);
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (isset($this->hFile)) {
      fclose($this->hFile);
    }
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Log_Csv or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    return new AblePolecat_Log_Csv();
  }
}