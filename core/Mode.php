<?php
/**
 * @file: Mode.php
 * The Mode object encapsulates application environment configuration and
 * error/exception handling. Able Polecat loads three basic modes sequentially,
 * similar to OS protection rings: 
 * Server Mode - handles configuration of core class library
 * Application Mode - handles configuration of extended functionality (e.g. modules)
 * Session Mode - handles state of current session.
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Syslog.php')));

interface AblePolecat_ModeInterface extends AblePolecat_AccessControl_SubjectInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * @return AblePolecat_EnvironmentInterface.
   */
  public function getEnvironment();
}

abstract class AblePolecat_ModeAbstract implements AblePolecat_ModeInterface {
  
  /**
   * @var AblePolecat_AccessControl_AgentInterface
   */
  private $Agent;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $Environment;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  abstract protected function initialize();
  
  /**
   * @return AblePolecat_AccessControl_AgentInterface or NULL
   */
  protected function getAgent() {
    return $this->Agent;
  }
  
  /**
   * @param AblePolecat_AccessControl_AgentInterface $Agent
   */
  protected static function setAgent(AblePolecat_AccessControl_AgentInterface $Agent) {
    $this->Agent = $Agent;
  }
  
  /**
   * @param AblePolecat_EnvironmentInterface $Environment.
   */
  protected function setEnvironment(AblePolecat_EnvironmentInterface $Environment) {
    $this->Environment = $Environment;
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
    $type = AblePolecat_LogInterface::STATUS;
    switch($errno) {
      default:
        break;
      case E_USER_ERROR:
      case E_ERROR:
        $type = AblePolecat_LogInterface::ERROR;
        break;
      case E_USER_WARNING:
        $type = AblePolecat_LogInterface::WARNING;
        break;
    }
    AblePolecat_Log_Syslog::wakeup()->putMessage($type, $msg);
    if ($die) {
      die("Fatal error in Able Polecat ($errstr)");
    }
    return $die;
  }
  
  /**
   * Used to log exceptions thrown before user logger(s) initialized.
   */
  public static function defaultExceptionHandler($Exception) {
    
    $msg = sprintf("Unhandled exception (%d) in Able Polecat. %s line %d : %s", 
      $Exception->getCode(),
      $Exception->getFile(),
      $Exception->getLine(),
      $Exception->getMessage()
    );
    $message = "$msg ({$_SERVER['REMOTE_ADDR']},  ({$_SERVER['HTTP_USER_AGENT']}))";
    AblePolecat_Log_Syslog::wakeup()->putMessage($type, $message);
    die($msg);
  }
  
  /**
   * @return AblePolecat_EnvironmentInterface.
   */
  public function getEnvironment() {
    return $this->Environment;
  }
  
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    $args = func_get_args();
    if (isset($args[0]) && is_a($args[0], 'AblePolecat_AccessControl_AgentInterface')) {
      $this->Agent = $args[0];
    }
    else {
      $this->Agent = NULL;
    }
    $this->Environment = NULL;
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}