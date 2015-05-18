<?php
/**
 * @file      polecat/core/Mode.php
 * @brief     A link in the command processing chain of responsibility.
 *
 * Able Polecat Modes are similar to OS protection rings, in terms of access control,
 * but serve also as an implementation of the chain of responsibility (COR) design 
 * pattern, either processing a command or passing it to the next, higher level of 
 * responsibility in the the chain/hierarchy.
 *
 * The simplest COR hierarchy in Able Polecat:
 * Server Mode - Receives HTTP request, sends HTTP response
 * Application Mode - Handles interaction between Able Polecat objects via class methods. 
 * User Mode - Handles interaction between Able Polecat objects and user session.
 *
 * Important responsibilities of the Mode class:
 * 1. Handle errors and exceptions.
 * 2. Encapsulate environment configuration settings.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Chain.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Mode.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Overloadable.php')));

interface AblePolecat_ModeInterface extends 
  AblePolecat_Command_TargetInterface,
  AblePolecat_OverloadableInterface {
  
  const ARG_SUBJECT         = 'subject';
  const ARG_REVERSE_TARGET  = 'reverseTarget';
  
  /**
   * Boot state messages.
   */
  const BOOT_STATE_BEGIN    = 0x00000000;
  const BOOT_STATE_ERR_INIT = 0x00000001;
  const BOOT_STATE_CONFIG   = 0x00000010;
  const BOOT_STATE_DATABASE = 0x00000100;
  
  const BOOT_STATE_FINISH   = 0x10000000;
  
  /**
   * Returns assigned value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   *
   * @return mixed Assigned value of given variable or NULL.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function getEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name);
  
  /**
   * Assign value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   * @param mixed $value Value of variable.
   *
   * @return bool TRUE if variable is set, otherwise FALSE.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function setEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name, $value);
  
  /**
   * Shut down Able Polecat server and send HTTP response.
   *
   * @param string  $reason   Reason for shut down.
   * @param string  $message  Message associated with shut down request.
   * @param int     $status   Return code.
   */
  public static function shutdown($reason, $message, $status = 0);
}

abstract class AblePolecat_ModeAbstract 
  extends AblePolecat_Command_TargetAbstract 
  implements AblePolecat_ModeInterface {
  
  /**
   * @var Array Data of state of boot (start up) process.
   */
  private static $bootState;
  
  /**
   * @var int Error display directive.
   */
  private static $display_errors;
  
  /**
   * @var int Error reporting directive.
   */
  private static $report_errors;
  
  /**
   * @var AblePolecat_Log_Boot
   */
  private $BootLog;
  
  /********************************************************************************
   * Implementation of AblePolecat_OverloadableInterface.
   ********************************************************************************/
  
  /**
   * Marshall numeric-indexed array of variable method arguments.
   *
   * @param string $method_name __METHOD__ is good enough.
   * @param Array $args Variable list of arguments passed to method (i.e. get_func_args()).
   * @param mixed $options Reserved for future use.
   *
   * @return Array Associative array representing [argument name] => [argument value]
   */
  public static function unmarshallArgsList($method_name, $args, $options = NULL) {
    
    $ArgsList = AblePolecat_ArgsList::create();
    
    foreach($args as $key => $value) {
      switch ($method_name) {
        default:
          break;
        case 'wakeup':
          switch($key) {
            case 0:
              $ArgsList->{AblePolecat_ModeInterface::ARG_SUBJECT} = $value;
              break;
            case 1:
              if (is_object($value) && is_a($value, 'AblePolecat_Command_TargetInterface')) {
                $ArgsList->{AblePolecat_ModeInterface::ARG_REVERSE_TARGET} = $value;
              }
              else {
                $message = sprintf('AblePolecat_ModeInterface::wakeup() argument #s must be type AblePolecat_Command_TargetInterface, %s passed.',
                  AblePolecat_Data::getDataTypeName($value)
                );
                throw new AblePolecat_Mode_Exception($message);
              }
              break;
          }
          break;
      }
    }
    return $ArgsList;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_ModeInterface.
   ********************************************************************************/
   
  /**
   * Shut down Able Polecat server and send HTTP response.
   *
   * @param string  $reason   Reason for shut down.
   * @param string  $message  Message associated with shut down request.
   * @param int     $status   Return code.
   */
  public static function shutdown($reason, $message, $status = 0) {
    exit($status);
  }
  
  /********************************************************************************
   * Error and exception handling functions.
   ********************************************************************************/
  
  /**
   * Configure error reporting/handling.
   */
  protected function initializeErrorReporting() {
    self::$report_errors = E_ALL;
    self::$display_errors = 0;
    if (isset($_REQUEST['display_errors'])) {
      $display_errors = strval($_REQUEST['display_errors']);
      switch ($display_errors) {
        default:
          self::$display_errors = E_ALL;
          break;
        case 'strict':
          self::$report_errors = E_STRICT;
          self::$display_errors = E_STRICT;
          break;
      }
      
      //
      // Error settings for local development only
      //
      error_reporting(self::$report_errors);
      ini_set('display_errors', self::$display_errors);
    }
    else {
      //
      // Error settings for production web server
      //
      error_reporting(self::$report_errors);
      ini_set('display_errors', self::$display_errors);
    }
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Alias for AblePolecat_CacheObjectAbstract::getDefaultCommandInvoker().
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  protected function getAgent() {
    return $this->getDefaultCommandInvoker();
  }
  
  /**
   * Return information about current state of boot (start up process).
   *
   * @return Array.
   */
  public static function getBootState() {
    return self::$bootState;
  }
  
  /**
   * Report current progress on boot (start up process).
   *
   * @param int $code
   * @param string $message
   */
  public static function reportBootState($code, $message) {
    
    $backtrace = debug_backtrace();
    if (isset($backtrace[1])) {
      isset($backtrace[1]['class']) ? $class = $backtrace[1]['class'] : $class = '';
      isset($backtrace[1]['type']) ? $type = $backtrace[1]['type'] : $type = '';
      isset($backtrace[1]['function']) ? $function = $backtrace[1]['function'] : $function = '';
      if (!isset(self::$bootState)) {
        self::$bootState = array();
      }
      self::$bootState['method'] = $class . $type . $function;
      self::$bootState['code'] = $code;
      self::$bootState['message'] = $message;
    }
  }
  
  /**
   * Write information to the boot log if it is open.
   *
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   * 
   * @return mixed Message as sent, if written, otherwise FALSE.
   */
  protected function putBootMessage($type, $msg) {    
    if (self::$display_errors) {
      switch (self::$bootState) {
        default:
          $this->BootLog = AblePolecat_Log_Boot::wakeup($this->getAgent());
          break;
        case self::BOOT_STATE_BEGIN:
        case self::BOOT_STATE_ERR_INIT:
          //
          // Before self::BOOT_STATE_CONFIG
          //
          $this->BootLog = AblePolecat_Log_Syslog::wakeup($this->getAgent());
          break;
      }
      $this->BootLog->putMessage($type, $msg);
    }
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    //
    // Access control agent (system agent).
    // @todo: wakeup() subject should be session.
    //
    $this->setDefaultCommandInvoker(AblePolecat_AccessControl_Agent_User_System::wakeup());
  }
}