<?php
/**
 * @file: Boot.php
 * Saves messages in a file if something causes bootstrap procedure to fail.
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
   * @var bool TRUE if an error has been logged, otherwise FALSE.
   */
  private $error;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    
    //
    // Start with no error condition.
    //
    $this->error = FALSE;
    
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
        $this->error = TRUE;
        break;
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
    // @todo:
    //
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    //
    // Only save messages to file in the event of an error during bootstrap.
    //
    if (isset(self::$Log) && $this->error) {
      //
      // Message indicating end of logging.
      //
      $msg = sprintf("Close boot log file");
      $this->putMessage(AblePolecat_LogInterface::STATUS, $msg);
      
      $file_name = AblePolecat_Server_Paths::getFullPath('logs') . DIRECTORY_SEPARATOR . 'boot.csv';
      $flog = @fopen($file_name, 'a');
      if ($flog) {
        // 
        // Banner separating this block from others
        //
        $now = date('Y-m-d H:i:s u e', time());
        $pad = str_pad('', 80, '#');
        fputs($flog, $pad . "\n");
        fputs($flog, '# BEGIN: ' . $now . "\n");
        fputs($flog, $pad . "\n");
  
        //
        // Write any available information about request which caused fail condition
        //
        isset($_SERVER['HTTP_HOST']) ? fputs($flog, 'HTTP_HOST = ' . $_SERVER['HTTP_HOST'] . "\n") : NULL;
        isset($_SERVER['HTTP_USER_AGENT']) ? fputs($flog, 'HTTP_USER_AGENT = ' . $_SERVER['HTTP_USER_AGENT'] . "\n") : NULL;
        isset($_SERVER['HTTP_ACCEPT']) ? fputs($flog, 'HTTP_ACCEPT = ' . $_SERVER['HTTP_ACCEPT'] . "\n") : NULL;
        isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? fputs($flog, 'HTTP_ACCEPT_LANGUAGE = ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'] . "\n") : NULL;
        isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? fputs($flog, 'HTTP_ACCEPT_ENCODING = ' . $_SERVER['HTTP_ACCEPT_ENCODING'] . "\n") : NULL;
        isset($_SERVER['HTTP_REFERER']) ? fputs($flog, 'HTTP_REFERER = ' . $_SERVER['HTTP_REFERER'] . "\n") : NULL;
        isset($_SERVER['HTTP_CONNECTION']) ? fputs($flog, 'HTTP_CONNECTION = ' . $_SERVER['HTTP_CONNECTION'] . "\n") : NULL;
        isset($_SERVER['HTTP_CACHE_CONTROL']) ? fputs($flog, 'HTTP_CACHE_CONTROL = ' . $_SERVER['HTTP_CACHE_CONTROL'] . "\n") : NULL;
        isset($_SERVER['SERVER_PORT']) ? fputs($flog, 'SERVER_PORT = ' . $_SERVER['SERVER_PORT'] . "\n") : NULL;
        isset($_SERVER['REMOTE_ADDR']) ? fputs($flog, 'REMOTE_ADDR = ' . $_SERVER['REMOTE_ADDR'] . "\n") : NULL;
        isset($_SERVER['SCRIPT_FILENAME']) ? fputs($flog, 'SCRIPT_FILENAME = ' . $_SERVER['SCRIPT_FILENAME'] . "\n") : NULL;
        isset($_SERVER['REMOTE_PORT']) ? fputs($flog, 'REMOTE_PORT = ' . $_SERVER['REMOTE_PORT'] . "\n") : NULL;
        isset($_SERVER['GATEWAY_INTERFACE']) ? fputs($flog, 'GATEWAY_INTERFACE = ' . $_SERVER['GATEWAY_INTERFACE'] . "\n") : NULL;
        isset($_SERVER['SERVER_PROTOCOL']) ? fputs($flog, 'SERVER_PROTOCOL = ' . $_SERVER['SERVER_PROTOCOL'] . "\n") : NULL;
        isset($_SERVER['REQUEST_METHOD']) ? fputs($flog, 'REQUEST_METHOD = ' . $_SERVER['REQUEST_METHOD'] . "\n") : NULL;
        isset($_SERVER['QUERY_STRING']) ? fputs($flog, 'QUERY_STRING = ' . $_SERVER['QUERY_STRING'] . "\n") : NULL;
        isset($_SERVER['REQUEST_URI']) ? fputs($flog, 'REQUEST_URI = ' . $_SERVER['REQUEST_URI'] . "\n") : NULL;
        isset($_SERVER['SCRIPT_NAME']) ? fputs($flog, 'SCRIPT_NAME = ' . $_SERVER['SCRIPT_NAME'] . "\n") : NULL;
        fputs($flog, $pad . "\n");
        
        //
        // Write Able Polecat message stream
        //
        $message = array_shift($this->messages);
        while (isset($message)) {
          fputcsv($flog, $message);
          $message = array_shift($this->messages);
        }
        
        //
        // Terminate
        //
        fputs($flog, $pad . "\n");
        fputs($flog, '# END: ' . $now . "\n");
        fputs($flog, $pad . "\n");
      }
      else {
        $flog = NULL;
        $msg = sprintf(
          "Able Polecat attempted to open a CSV log file in the directory given at %s. No such directory exists or it is not writable by web agent.",
          AblePolecat_Server_Paths::getFullPath('logs')
        );
        AblePolecat_Server::handleCriticalError(AblePolecat_Error::BOOTSTRAP_LOGGER, $msg);
      }
      
      if (isset($flog)) {
        fclose($flog);
        $flog = NULL;
      }
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