<?php
/**
 * @file      polecat/core/Log/Boot.php
 * @brief     Saves messages in a file if something causes bootstrap procedure to fail.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */
 
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Clock.php');
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Log.php');

class AblePolecat_Log_Boot extends AblePolecat_LogAbstract {
  
  /**
   * log file names.
   */
  const LOG_NAME_DEBUG = 'debug.csv';
  const LOG_NAME_ERROR = 'error.csv';
  
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
  // private $messages;
  
  /**
   * @var bool TRUE if an error has been logged, otherwise FALSE.
   */
  private $error;
  
  /**
   * @var resource File.
   */
  private $flog;
  
  /**
   * @var string File name assigned in ./usr/etc/polecat/conf/path.config.
   */
  private $filePath;
  
  /**
   * @var int boot procedure step number.
   */
  private $step;
  
  /**
   * Helper function. Queue messages.
   * 
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   */
  public function putMessage($type, $msg) {
    
    $fout = FALSE;
    if ($type === AblePolecat_LogInterface::ERROR) {
      $fout = $this->getOutput(AblePolecat_LogInterface::ERROR);
    }
    else {
      $fout = $this->getOutput(AblePolecat_LogInterface::DEBUG);
    }
    
    
    if ($fout) {
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
      // $this->messages[] = array(
        // 'time' => $time,
        // 'type' => $type,
        // 'body' => $message,
      // );
      $messageLine = array(
        'step' => $this->step,
        'time' => $time,
        'type' => $type,
        'body' => $message,
      );
      
      fputcsv($fout, $messageLine);
      $this->step += 1;
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
    //
    // @todo:
    //
  }
  
  /**
   * Dump raw request data to a file.
   */
  public function dumpRawRequest() {
    
    $fout = $this->getOutput(AblePolecat_LogInterface::DEBUG);
    if ($fout) {
      // 
      // Banner separating this block from others
      //
      $now = date('Y-m-d H:i:s u e', time());
      $pad = str_pad('', 80, '#');
      fputs($fout, $pad . "\n");
      fputs($fout, '# BEGIN: ' . $now . "\n");
      fputs($fout, $pad . "\n");

      //
      // Write any available information about request which caused fail condition
      //
      isset($_SERVER['HTTP_HOST']) ? fputs($fout, 'HTTP_HOST = ' . $_SERVER['HTTP_HOST'] . "\n") : NULL;
      isset($_SERVER['HTTP_USER_AGENT']) ? fputs($fout, 'HTTP_USER_AGENT = ' . $_SERVER['HTTP_USER_AGENT'] . "\n") : NULL;
      isset($_SERVER['HTTP_ACCEPT']) ? fputs($fout, 'HTTP_ACCEPT = ' . $_SERVER['HTTP_ACCEPT'] . "\n") : NULL;
      isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? fputs($fout, 'HTTP_ACCEPT_LANGUAGE = ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'] . "\n") : NULL;
      isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? fputs($fout, 'HTTP_ACCEPT_ENCODING = ' . $_SERVER['HTTP_ACCEPT_ENCODING'] . "\n") : NULL;
      isset($_SERVER['HTTP_REFERER']) ? fputs($fout, 'HTTP_REFERER = ' . $_SERVER['HTTP_REFERER'] . "\n") : NULL;
      isset($_SERVER['HTTP_CONNECTION']) ? fputs($fout, 'HTTP_CONNECTION = ' . $_SERVER['HTTP_CONNECTION'] . "\n") : NULL;
      isset($_SERVER['HTTP_CACHE_CONTROL']) ? fputs($fout, 'HTTP_CACHE_CONTROL = ' . $_SERVER['HTTP_CACHE_CONTROL'] . "\n") : NULL;
      isset($_SERVER['SERVER_PORT']) ? fputs($fout, 'SERVER_PORT = ' . $_SERVER['SERVER_PORT'] . "\n") : NULL;
      isset($_SERVER['REMOTE_ADDR']) ? fputs($fout, 'REMOTE_ADDR = ' . $_SERVER['REMOTE_ADDR'] . "\n") : NULL;
      isset($_SERVER['SCRIPT_FILENAME']) ? fputs($fout, 'SCRIPT_FILENAME = ' . $_SERVER['SCRIPT_FILENAME'] . "\n") : NULL;
      isset($_SERVER['REMOTE_PORT']) ? fputs($fout, 'REMOTE_PORT = ' . $_SERVER['REMOTE_PORT'] . "\n") : NULL;
      isset($_SERVER['GATEWAY_INTERFACE']) ? fputs($fout, 'GATEWAY_INTERFACE = ' . $_SERVER['GATEWAY_INTERFACE'] . "\n") : NULL;
      isset($_SERVER['SERVER_PROTOCOL']) ? fputs($fout, 'SERVER_PROTOCOL = ' . $_SERVER['SERVER_PROTOCOL'] . "\n") : NULL;
      isset($_SERVER['REQUEST_METHOD']) ? fputs($fout, 'REQUEST_METHOD = ' . $_SERVER['REQUEST_METHOD'] . "\n") : NULL;
      isset($_SERVER['QUERY_STRING']) ? fputs($fout, 'QUERY_STRING = ' . $_SERVER['QUERY_STRING'] . "\n") : NULL;
      isset($_SERVER['REQUEST_URI']) ? fputs($fout, 'REQUEST_URI = ' . $_SERVER['REQUEST_URI'] . "\n") : NULL;
      isset($_SERVER['SCRIPT_NAME']) ? fputs($fout, 'SCRIPT_NAME = ' . $_SERVER['SCRIPT_NAME'] . "\n") : NULL;
      fputs($fout, $pad . "\n");
      
      //
      // Terminate
      //
      fputs($fout, $pad . "\n");
      fputs($fout, '# END: ' . $now . "\n");
      fputs($fout, $pad . "\n");
    }
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (isset(self::$Log)) {
      try {
        parent::sleep();
        $fout = $this->getOutput(AblePolecat_LogInterface::DEBUG);
        if (isset($fout)) {
          //
          // Message indicating end of debug logging.
          //
          $msg = sprintf("Close boot log file");
          $this->putMessage(AblePolecat_LogInterface::STATUS, $msg);
          
          //
          // A separator to make reading across sessions easier.
          //
          $terminate = array('########', '########', '########', '########');
          fputcsv($fout, $terminate);
        }
        
        //
        // Close all output streams.
        //
        $this->closeOutput();
      }
      catch (AblePolecat_Exception $Exception) {
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
      self::$Log = new AblePolecat_Log_Boot($Subject);
    }
    return self::$Log;
  }
  
  /**
   * Close all open output streams.
   */
  protected function closeOutput() {
    foreach ($this->flog as $type => $fout) {
      fclose($fout);
      unset($this->flog[$type]);
    }
  }
  
  /**
   * Return the requested output stream if available.
   *
   * @param string $type ERROR | DEBUG.
   *
   * @return mixed Resource (file) or NULL.
   */
  protected function getOutput($type) {
    
    //
    // Default to FALSE.
    //
    $fout = FALSE;
    
    switch ($type) {
      default:
        break;
      case AblePolecat_LogInterface::DEBUG:
      case AblePolecat_LogInterface::ERROR:
        if (isset($this->flog[$type])) {
          $fout = $this->flog[$type];
        }
        else {
          if (isset($this->filePath[$type])) {
            $fout = @fopen($this->filePath[$type], 'a');
            if ($fout) {
              $this->flog[$type] = $fout;
            }
          }
        }
        break;
    }
    return $fout;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    
    $this->Clock = new AblePolecat_Clock();
    
    //
    // Errors are always logged.
    //
    $this->flog = array();
    $this->filePath = array(
      AblePolecat_LogInterface::ERROR => implode(DIRECTORY_SEPARATOR, array(
          AblePolecat_Server_Paths::getFullPath('log'), 
          AblePolecat_Log_Boot::LOG_NAME_ERROR,
        )
      ),
    );
    
    //
    // Debug log is not opened unless display_errors paramter is set in request quesry string.
    //
    isset($_REQUEST['display_errors']) ? $display_errors = $_REQUEST['display_errors'] : $display_errors = FALSE;
    switch ($display_errors) {
      default:
        $display_errors = FALSE;
        break;
      case 'all':
      case 'strict':
        break;
    }
    if ($display_errors) {
      $this->filePath[AblePolecat_LogInterface::DEBUG] = implode(DIRECTORY_SEPARATOR, array(
        AblePolecat_Server_Paths::getFullPath('log'), 
        AblePolecat_Log_Boot::LOG_NAME_DEBUG,
        )
      );
      // $this->filePath = AblePolecat_Server_Paths::getFullPath('log'); // self::LOG_NAME_BOOTSEQ;
      // global $ABLE_POLECAT_BOOT_LOG;
      // $this->filePath = $ABLE_POLECAT_BOOT_LOG;
      // if ($this->filePath && !file_exists($this->filePath)) {
        // $pathParts = explode(DIRECTORY_SEPARATOR, $this->filePath);
        // is_array($pathParts) ? $parentDir = array_pop($pathParts) : $parentDir = FALSE;
        // if ($parentDir && file_exists($parentDir) && !is_writeable($parentDir)) {
          // $this->filePath = FALSE;
        // }
      // }
    }
    
    if ($display_errors) {
      //
      // Start with no error condition.
      //
      $this->step = 1;
      $this->error = FALSE;
      // $this->messages = array();
      
      //
      // Start stop watch
      //
      $this->Clock->start();
      
      //
      // Initialize the output stream.
      //
      $DateTimeZone = new DateTimeZone('America/Chicago');
      $DateTime = new DateTime('now', $DateTimeZone);
      $msg = sprintf("Open boot log file @ %s", $DateTime->format('H:i:s u e'));
      $this->putMessage(AblePolecat_LogInterface::STATUS, $msg);
    }
    else {
      $this->step = -1;
      $this->error = FALSE;
      // $this->messages = array();
    }
  }
}