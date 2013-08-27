<?php
/**
 * @file: Server.php
 * Server as in 'client-server' and also as in $_SERVER[].
 *
 * Server has the following duties:
 * 1. Act as primary interface to web server (application) and system (OS)
 * 2. Provide default handling of errors and exceptions.
 * 3. Provide default logging.
 * 4. Ensure proper application server bootstrap.
 * 5. Load Application Server Environment Settings.
 */

//
// Sets paths for entire framework; must be first
//
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'ClassRegistry.php')));

//
// These are listed in the order they are created in initialize() and bootstrap()
//
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Log', 'Csv.php')));
include_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Mode', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Http', 'Request.php')));

interface AblePolecat_ServerInterface {
  
  /**
   * Bootstrap procedure for Able Polecat.
   *
   * @return AblePolecat_ServerInterface Bootstrapped system object.
   */
  public static function bootstrap();
  
  /**
   * @return AblePolecat_Mode_ServerAbstract.
   */
  public static function getServerMode();
  
  /**
   * Get handle the service bus.
   *
   * @return AblePolecat_Service_BusInterface or NULL.
   */
  public static function getServiceBus();
  
  /**
   * Log a message to standard/default log (file).
   * 
   * @param string $severity error | warning | status | info | debug.
   * @param mixed  $message Message body.
   * @param int    $code Error code.
   */
  public static function log($severity, $message, $code = NULL);
  
  /**
   * Similar to DOM ready() but for Able Polecat core system.
   *
   * @return AblePolecat_ServerInterface Bootstrapped system object.
   */
  public static function ready();
}

class AblePolecat_Server implements AblePolecat_ServerInterface {
  
  /**
   * @var AblePolecat_Server Singleton instance.
   */
  private static $Server = NULL;
  
  /**
   * @var bool Prevents some code from exceuting prior to bootstrap completing.
   */
  private static $ready = FALSE;
  
  /**
   * @var string DEV | QA | USER.
   */
  private static $boot_mode = NULL;
  
  /**
   * @var Array Loggers used for saving status, error messages etc.
   */
  private $DefaultLog;
  
  /**
   * @var 
   */
  private $ClassRegistry;
  
  /**
   * @var AblePolecat_Mode_ServerAbstract.
   */
  protected $ServerMode;
  
  /**
   * @var Service bus.
   */
  protected $ServiceBus;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    //
    // This code checks query string for a boot mode parameter named 'run'.
    // If passed, and mode is authorized for client, server will boot in
    // requested mode. If parameter is not passed, but mode is stored in a 
    // cookie, server will boot in cookie mode. Otherwise, the server will 
    // boot in normal mode.
    //
    $run_var = AblePolecat_Http_Request::getVariable('run');
    if (!isset($run_var)) {
      //
      // If runtime context was saved in a cookie, use that until agent
      // explicitly unsets with run=user or cookie expires.
      //
      if (isset($_COOKIE['ABLE_POLECAT_RUNTIME'])) {
        $data = unserialize($_COOKIE['ABLE_POLECAT_RUNTIME']);
        isset($data['context']) ? $run_var = $data['context'] : NULL;
      }
    }
    switch ($run_var) {
      default:
        self::$boot_mode = 'user';
        break;
      case 'dev':
      case 'qa':
      case 'user':
        self::$boot_mode = $run_var;
        break;
    }
    
    //
    // Wakeup default log file and class registry.
    //
    $this->DefaultLog = AblePolecat_Log_Csv::wakeup();
    $this->ClassRegistry = AblePolecat_ClassRegistry::wakeup();
    
    //
    // These are initialzied in bootstrap().
    //
    $this->ServerMode = NULL;
    $this->ServiceBus = NULL;
  }
  
  /**
   * @param AblePolecat_Mode_ServerAbstract $ServerMode.
   */
  protected function setServerMode(AblePolecat_Mode_ServerAbstract $ServerMode) {
    $this->ServerMode = $ServerMode;
  }
  
  /**
   * Log a message to standard/default log (file).
   * 
   * @param string $severity error | warning | status | info | debug.
   * @param mixed  $message Message body.
   * @param int    $code Error code.
   */
  protected function writeToDefaultLog($severity, $message, $code = NULL) {
    if (isset($this->DefaultLog)) {
      switch ($severity) {
        default:
          $this->DefaultLog->logStatusMessage($message);
          break;
        case AblePolecat_LogInterface::STATUS:
          $this->DefaultLog->logStatusMessage($message);
          break;
        case AblePolecat_LogInterface::WARNING:
          $this->DefaultLog->logWarningMessage($message);
          break;
        case AblePolecat_LogInterface::ERROR:
          $this->DefaultLog->logErrorMessage($message);
          break;
        case AblePolecat_LogInterface::DEBUG:
          $this->DefaultLog->logStatusMessage($message);
          break;
      }
    }
  }
  
  /**
   * Bootstrap procedure for Able Polecat.
   *
   * @return AblePolecat_ServerInterface Bootstrapped system object.
   */
  public static function bootstrap() {
    
    //
    // AblePolecat_Server implements Singelton design pattern.
    //
    if (!isset(self::$Server)) {
      //
      // Create instance of Singleton.
      //
      $Server = new self();
      
      //
      // Server mode
      // 1. Initialize error reporting
      // 2. Set default error handler
      // 3. Set default exception handler
      // 4. Normal | Development | Testing mode
      //
      switch(self::$boot_mode) {
        default:
          require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Mode', 'Server', 'Normal.php')));
          $Server->setServerMode(AblePolecat_Mode_Normal::wakeup());
          break;
        case 'dev':
          require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Mode', 'Server', 'Dev.php')));
          $Server->setServerMode(AblePolecat_Mode_Dev::wakeup());
          break;
        case 'qa':
          require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Mode', 'Server', 'Qa.php')));
          $Server->setServerMode(AblePolecat_Mode_Qa::wakeup());
          break;
      }
    
    //
    // @todo: 
    // Port module logging feature from AblePolecat_Environment to AblePolecat_Mode_Application
    // Change AblePolecat_EnvironmentInterface::bootstrap() to ::load()
    // Implement AblePolecat_EnvironmentInterface for Server, Application, User
	  // @todo:  AblePolecat_Environment_Server::load();
	  // 1. Class registry
	  // 2. Class loader
	  // 3. Load conf file
	  //
	  
	  //
	  // @todo: AblePolecat_Mode_Application::start();
	  // @see:  AblePolecat_Environment_Application::load();
	  // 1. Module registry
	  // 2. Module conf files
	  // 3. Load modules as directed
	  //
	  
	  //
	  // @todo:  AblePolecat_Mode_User::start();
	  // @see:  AblePolecat_Environment_User::load();
	  // 1. Session management
	  // 2. Cookies
	  // 3. Other stored user settings (database).
	  //
      
      // @TODO:
      // Port default error/exception handling from bootmode.php
      // Port cookie stuff in bootmode.php to AblePolecat_Environment_User (new)
      // Port rest of stuff in bootmode.php to appropriate AblePolecat_Server_Mode class
      // Use AblePolecat_Http_Request::getVariable('mode') to get boot mode.
      // Create instance of AblePolecat_Server_ModeInterface accordingly
      // Get rid of pathdefs.php and bootmode.php
      // Port default logger (CSV) implementation from AblePolecat_Environment_Default
      // AblePolecat_Environment_Server replaces AblePolecat_Environment_Default and will...
      //   1. Encapsulate class registry and loader
      //   2. Encapsulate server configuration (./polecat_root/conf)
      // AblePolecat_Environment_User will encapulate contributed modules stuff
      //
      
      //
      // Bootstrap completed successfully
      //
      self::$Server = $Server;
      self::$ready = TRUE;
    }
    return self::$Server;
  }
  
  /**
   * @return string dev | qa | user.
   */
  public static function getBootMode() {
    
    $BootMode = NULL;
    switch (self::$boot_mode) {
      default:
        break;
      case 'dev':
      case 'qa':
      case 'user':
        $BootMode = self::$boot_mode;
        break;
    }
    if (!isset($BootMode)) {
      self::handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION, 'Call for unitialized boot mode.');
    }
    return $BootMode;
  }
  
  /**
   * @return AblePolecat_Mode_ServerAbstract or NULL.
   */
  public static function getServerMode() {
    $ServerMode = NULL;
    $Server = self::ready();
    if ($Server) {
      $ServerMode = $Server->ServerMode;
    }
    return $ServerMode;
  }
  
  /**
   * @return AblePolecat_Service_BusInterface or NULL.
   */
  public static function getServiceBus() {
    $ServiceBus = NULL;
    $Server = self::ready();
    if ($Server) {
      $ServiceBus = $Server->ServiceBus;
    }
    return $ServiceBus;
  }
  
  /**
   * Handle critical environment errors depending on runtime context.
   */
  public static function handleCriticalError($error_number, $error_message = NULL) {
    
    !isset($error_message) ? $error_message = ABLE_POLECAT_EXCEPTION_MSG($error_number) : NULL;
    $Server = self::ready();
    if ($Server) {
      self::log(AblePolecat_LogInterface::ERROR, $error_message, $error_number);
      $ServerModeClass = get_class(self::getServerMode());
      switch ($ServerModeClass) {
        case 'AblePolecat_Mode_Dev':
          //
          // Override SEH - trigger error and die.
          //
          trigger_error($error_message, E_USER_ERROR);
          break;
        default:
          //
          // throw exception
          //
          throw new AblePolecat_Environment_Exception($error_message, $error_number);
          break;
      }
    }
    else {
      trigger_error($error_message, E_USER_ERROR);
    }
  }
  
  /**
   * Log a message to standard/default log (file).
   * 
   * @param string $severity error | warning | status | info | debug.
   * @param mixed  $message Message body.
   * @param int    $code Error code.
   */
  public static function log($severity, $message, $code = NULL) {
    $Server = self::ready();
    if ($Server) {
      $type = AblePolecat_LogInterface::INFO;
      switch ($severity) {
        default:
          break;
        case AblePolecat_LogInterface::STATUS:
        case AblePolecat_LogInterface::WARNING:
        case AblePolecat_LogInterface::ERROR:
        case AblePolecat_LogInterface::DEBUG:
          $type = $severity;
          break;
      }
      $Server->writeToDefaultLog($type, $message, $code);
    }
  }
  
  /**
   * Similar to DOM ready() but for Able Polecat core system.
   *
   * @return AblePolecat_ServerInterface or FALSE.
   */
  public static function ready() {
    $ready = self::$ready;
    if ($ready) {
      $ready = self::$Server;
    }
    return $ready;
  }
  
  final protected function __construct() {
    $this->initialize();
  }
}

/**
 * Exceptions thrown by Able Polecat Server.
 */
class AblePolecat_Server_Exception extends AblePolecat_Exception {
}