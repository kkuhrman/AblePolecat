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
   * @return string dev | qa | user.
   */
  public static function getBootMode();
  
  /**
   * @return AblePolecat_ClassRegistryInterface.
   */
  public static function getClassRegistry();
  
  /**
   * @return AblePolecat_LogInterface.
   */
  public static function getDefaultLog();
  
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
   * Handle critical environment errors depending on runtime context.
   * 
   * @param int $error_number Predefined Able Polecat error constant or E_USER_ERROR.
   * @param mixed $error_message The body of the error message.
   *
   * If $error_code is a predefined Able Polecat error constant and $error_message
   * is not given, the default error message from Exception.php will be used.
   */
  public static function handleCriticalError($error_number, $error_message = NULL);
  
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
   * Resource protection ring assignments.
   */
  const RING_BOOT_MODE        = 0;
  const RING_DEFAULT_LOG      = 0;
  const RING_CLASS_REGISTRY   = 0;
  const RING_SERVER_MODE      = 0;
  const RING_SERVICE_BUS      = 0;
  
  /**
   * Internal resource names.
   */
  const NAME_BOOT_MODE        = 'boot mode';
  const NAME_DEFAULT_LOG      = 'default log';
  const NAME_CLASS_REGISTRY   = 'class registry';
  const NAME_SERVER_MODE      = 'server mode';
  const NAME_SERVICE_BUS      = 'service bus';
  
  /**
   * @var AblePolecat_Server Singleton instance.
   */
  private static $Server = NULL;
  
  /**
   * @var bool Prevents some code from exceuting prior to bootstrap completing.
   */
  private static $ready = FALSE;
  
  /**
   * @var Array $Resources.
   *
   * Resources are cached to Able Polecat Server according to a model similar to
   * OS protection rings. They are stored according to order in which the Server 
   * initializes. This allows user to call for lower level resources prior to higher
   * levels being initialized.
   *
   * They are stored as Array([zero-based protection ring number] => [internal resource name]);
   */
  private static $Resources = NULL;
    
  /**
   * Initialize resources in protection ring '0' (e.g. kernel).
   */
  protected function initialize() {
    
    //
    // 'Kernel' resources container.
    //
    self::$Resources[0] = array();
    
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
    $BootMode = 'user';
    switch ($run_var) {
      default:
        break;
      case 'dev':
      case 'qa':
      case 'user':
        $BootMode = $run_var;
        break;
    }
    self::setResource(self::RING_BOOT_MODE, self::NAME_BOOT_MODE, $BootMode);
    
    //
    // Wakeup default log file.
    //
    $DefaultLog = AblePolecat_Log_Csv::wakeup();
    self::setResource(self::RING_DEFAULT_LOG, self::NAME_DEFAULT_LOG, $DefaultLog);
    
    //
    // Wakeup class registry.
    //
    $ClassRegistry = AblePolecat_ClassRegistry::wakeup();
    self::setResource(self::RING_CLASS_REGISTRY, self::NAME_CLASS_REGISTRY, $ClassRegistry);
    
    //
    // Server mode
    // 1. Initialize error reporting
    // 2. Set default error handler
    // 3. Set default exception handler
    // 4. Normal | Development | Testing mode
    //
    $ServerMode = NULL;
    switch(self::getBootMode()) {
      default:
        require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Mode', 'Server', 'Normal.php')));
        $ServerMode = AblePolecat_Mode_Normal::wakeup();
        break;
      case 'dev':
        require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Mode', 'Server', 'Dev.php')));
        $ServerMode = AblePolecat_Mode_Dev::wakeup();
        break;
      case 'qa':
        require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Mode', 'Server', 'Qa.php')));
        $ServerMode = AblePolecat_Mode_Qa::wakeup();
        break;
    }
    self::setResource(self::RING_SERVER_MODE, self::NAME_SERVER_MODE, $ServerMode);
    
    //
    // @todo: Service Bus
  }
  
  /**
   * Retrieves resource given by $name in protection ring given by $ring.
   *
   * @param int $ring Ring assignment.
   * @param string $name Internal name of resource.
   *
   * @return mixed The cached resource or NULL.
   *
   * @throw Exception if no resource stored at given location.
   */
  protected static function getResource($ring, $name) {
    
    $resource = NULL;
    if (isset(self::$Resources[$ring]) && isset(self::$Resources[$ring][$name])) {
      $resource = self::$Resources[$ring][$name];
    }
    else {
      self::handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION, 
        "Attempt to retrieve Able Polecat Server resource given by $name at protection ring $ring failed.");
    }
    return $resource;
  }
  
  /**
   * Caches resource given by $name in protection ring if available.
   *
   * @param int $ring Ring assignment.
   * @param string $name Internal name of resource.
   * @param mixed $resource The resource to cache.
   *
   * @throw Exception if ring is not intialized.
   */
  protected static function setResource($ring, $name, $resource) {
    if (isset(self::$Resources[$ring]) && is_array(self::$Resources[$ring])) {
      if (!isset(self::$Resources[$ring][$name])) {
        self::$Resources[$ring][$name] = $resource;
      }
    }
    else {
      self::handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION, 
        "Able Polecat Server rejected attempt to cache resource given by $name at protection ring $ring.");
    }
  }
  
  /**
   * Log a message to standard/default log (file).
   * 
   * @param string $severity error | warning | status | info | debug.
   * @param mixed  $message Message body.
   * @param int    $code Error code.
   */
  protected function writeToDefaultLog($severity, $message, $code = NULL) {
    $DefaultLog = self::getDefaultLog();
    switch ($severity) {
      default:
        $DefaultLog->logStatusMessage($message);
        break;
      case AblePolecat_LogInterface::STATUS:
        $DefaultLog->logStatusMessage($message);
        break;
      case AblePolecat_LogInterface::WARNING:
        $DefaultLog->logWarningMessage($message);
        break;
      case AblePolecat_LogInterface::ERROR:
        $DefaultLog->logErrorMessage($message);
        break;
      case AblePolecat_LogInterface::DEBUG:
        $DefaultLog->logStatusMessage($message);
        break;
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
    // @todo: 
    // Port module logging feature from AblePolecat_Environment to AblePolecat_Mode_Application
    // Implement AblePolecat_EnvironmentInterface for Server, Application, User
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
    return self::getResource(self::RING_BOOT_MODE, self::NAME_BOOT_MODE);
  }
  
  /**
   * @return AblePolecat_ClassRegistryInterface.
   */
  public static function getClassRegistry() {
    return self::getResource(self::RING_CLASS_REGISTRY, self::NAME_CLASS_REGISTRY);
  }
  
  /**
   * @return AblePolecat_LogInterface.
   */
  public static function getDefaultLog() {
    return self::getResource(self::RING_DEFAULT_LOG, self::NAME_DEFAULT_LOG);
  }
  
  /**
   * @return AblePolecat_Mode_ServerAbstract or NULL.
   */
  public static function getServerMode() {
    return self::getResource(self::RING_SERVER_MODE, self::NAME_SERVER_MODE);
  }
  
  /**
   * @return AblePolecat_Service_BusInterface or NULL.
   */
  public static function getServiceBus() {
    return self::getResource(self::RING_SERVICE_BUS, self::NAME_SERVICE_BUS);
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
    self::$Resources = array();
    $this->initialize();
  }
}

/**
 * Exceptions thrown by Able Polecat Server.
 */
class AblePolecat_Server_Exception extends AblePolecat_Exception {
}