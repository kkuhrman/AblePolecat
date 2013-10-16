<?php
/**
 * @file: Application.php
 * Base class for Application modes (second most protected).
 */

/**
 * Configurable paths are defined *after* server conf file is loaded.
 * Any use prior to this must use AblePolecat_Server_Paths::getFullPath().
 * This is best practice in any case rather than using global constants .
 */
 
//
// Contributed libraries directory.
//
if (!defined('ABLE_POLECAT_LIBS_PATH')) {
  $ABLE_POLECAT_LIBS_PATH = AblePolecat_Server_Paths::getFullPath('libs');
  define('ABLE_POLECAT_LIBS_PATH', $ABLE_POLECAT_LIBS_PATH);
}

//
// Contributed modules directory.
//
if (!defined('ABLE_POLECAT_MODS_PATH')) {
  $ABLE_POLECAT_MODS_PATH = AblePolecat_Server_Paths::getFullPath('mods');
  define('ABLE_POLECAT_MODS_PATH', $ABLE_POLECAT_MODS_PATH);
}

//
// Log files directory.
//
if (!defined('ABLE_POLECAT_LOGS_PATH')) {
  $ABLE_POLECAT_LOGS_PATH = AblePolecat_Server_Paths::getFullPath('logs');
  define('ABLE_POLECAT_LOGS_PATH', $ABLE_POLECAT_LOGS_PATH);
}

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Mode.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Environment', 'Application.php')));

class AblePolecat_Mode_Application extends AblePolecat_ModeAbstract {

  //
  // Application resource defs.
  //
  const RESOURCE_ALL              = 'all';
  
  /**
   * @var AblePolecat_Mode_ApplicationAbstract Concrete ApplicationMode instance.
   */
  protected static $ApplicationMode;
  
  /**
   * @var bool Prevents some code from exceuting prior to start().
   */
  protected static $ready = FALSE;
  
  /**
   * @var List of interfaces which can be used as application resources.
   */
  private static $supported_interfaces = NULL;
  
  /**
   * @var Array $Resources.
   *
   * Application resources are stored as Array([type] => [module name]).
   */
  private $Resources = NULL;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    self::$ApplicationMode = NULL;
    
    //
    // Supported Able Polecat interfaces.
    //
    $this->Resources = array();
    $supported_resources = self::getSupportedResourceInterfaces();
    foreach($supported_resources as $key => $interface_name) {
      $this->Resources[$interface_name] = array();
    }
    
    //
    // Check for required server resources.
    // (these will throw exception if not ready).
    //
    AblePolecat_Server::getBootMode();
    AblePolecat_Server::getClassRegistry();
    AblePolecat_Server::getDefaultLog();
    AblePolecat_Server::getServerMode();
    AblePolecat_Server::getServiceBus();
  }
  
  /**
   * Retrieves application resource(s).
   *
   * @param int $interface One of the supported interface types.
   * @param string $module Name of module (if 'all' - all resources of $interface are returned).
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return mixed The cached resource or NULL.
   *
   * @throw Exception if no resource stored at given location.
   * @see setResource().
   */
  public function getResource($interface, $module = self::RESOURCE_ALL, $safe = TRUE) {
    
    $resource = NULL;
    if (isset($this->Resources[$interface])) {
      if ($module === self::RESOURCE_ALL) {
        $resource = $this->Resources[$interface];
      }
      else if (isset($this->Resources[$interface][$module])) {
        $resource = $this->Resources[$interface][$module];
      }
    }
    if (!isset($resource) && $safe) {
      AblePolecat_Server::handleCriticalError(AblePolecat_Error::UNSUPPORTED_INTERFACE, 
        "Attempt to retrieve Able Polecat application resource of type $interface, module $module failed.");
    }
    return $resource;
  }
  
  /**
   * Return an array with the names of all supported resource interfaces.
   *
   * @return Array Names of supported resource interfaces.
   */
  public static function getSupportedResourceInterfaces() {
    
    //
    // @todo: perhaps this should be in conf file, database, other?
    //
    if (!isset(self::$supported_interfaces)) {
      self::$supported_interfaces = array(
        'AblePolecat_LogInterface',
      );
    }
    return self::$supported_interfaces;
  }
  
  /**
   * Indicates whether given interface/class combination qualifies as an application resource.
   *
   * @param int $interface Name of interface.
   * @param mixed $resource Object or class name.
   *
   * @return mixed Name of supported resource interface implemented by object/class or FALSE.
   */
  public static function isValidResource($interface, $resource) {
    
    $valid = array_key_exists($interface, array_flip(self::getSupportedResourceInterfaces()));
    
    if ($valid && isset($resource)) {
      if (is_object($resource)) {
        $valid = is_a($resource, $interface);
      }
      else if (is_string($resource)) {
        $valid = is_subclass_of($resource, $interface);
      }
      else {
        $valid = FALSE;
      }
    }
    return $valid;
  }
  
  /**
   * Stores an application resource.
   *
   * @param int $interface One of the supported interface types.
   * @param string $module Name of module.
   * @param mixed $resource The resource to cache.
   *
   * @return mixed Name of supported resource interface.
   * @throw Exception if resource does not implement a supoprted interface.
   */
  protected function setResource($interface, $module, $resource) {
    
    if (self::isValidResource($interface, $resource)) {
      if(!isset($this->Resources[$interface])) {
        $this->Resources[$interface] = array();
      }
      if (!isset($this->Resources[$interface][$module])) {
        $this->Resources[$interface][$module] = array();
      }
      $class_name = get_class($resource);
      if (!isset($this->Resources[$interface][$module][$class_name])) {
        $this->Resources[$interface][$module][$class_name] = $resource;
      }
    }
    else {
      $msg = sprintf("Able Polecat rejected attempt to store application resource for module %s. %s does not implement %s or %s is not a supported resource interface.",
        $module,
        get_class($resource),
        $interface,
        $interface
      );
      AblePolecat_Server::handleCriticalError(AblePolecat_Error::UNSUPPORTED_INTERFACE, $msg);
    }
  }
  
  /**
   * Load resources from registered modules based on class attribute load.
   * @throw AblePolecat_Server_Exception is application mode is not ready.
   */
  public function loadRegisteredResources() {
  
    $ApplicationMode = self::ready();
    if ($ApplicationMode) {
      $registeredClasses = AblePolecat_Server::getClassRegistry()->getModuleClasses('interface');   
      foreach($this->Resources as $interfaceName => $resources) {
        if (isset($registeredClasses[$interfaceName])) {
          foreach($registeredClasses[$interfaceName] as $moduleName => $moduleClasses) {
            foreach($moduleClasses as $id => $className) {
              $class = AblePolecat_Server::getClassRegistry()->loadClass($className);
              $ApplicationMode->setResource($interfaceName, $moduleName, $class);
            }
          }
        }
      }
    }
  }
  
  /**
   * Log a message to application log(s).
   * 
   * @param string $severity error | warning | status | info | debug.
   * @param mixed  $message Message body.
   * @param int    $code Error code.
   */
  public function log($severity, $message, $code = NULL) {
    
    $allRegisteredLogs = $this->getResource('AblePolecat_LogInterface', self::RESOURCE_ALL, FALSE);
    if (isset($allRegisteredLogs)) {
      foreach($allRegisteredLogs as $moduleName => $moduleRegisteredLogs) {
        foreach($moduleRegisteredLogs as $className => $log) {
          switch ($severity) {
            default:
              $log->logStatusMessage($message);
              break;
            case AblePolecat_LogInterface::STATUS:
              $log->logStatusMessage($message);
              break;
            case AblePolecat_LogInterface::WARNING:
              $log->logWarningMessage($message);
              break;
            case AblePolecat_LogInterface::ERROR:
              $log->logErrorMessage($message);
              break;
            case AblePolecat_LogInterface::DEBUG:
              $log->logStatusMessage($message);
              break;
          }
        }
      }
    }
  }
  
  /**
   * Similar to DOM ready() but for Able Polecat application mode.
   *
   * @return AblePolecat_Mode_ApplicationAbstract or FALSE.
   */
  public static function ready() {
    $ready = self::$ready;
    if ($ready) {
      $ready = self::$ApplicationMode;
    }
    return $ready;
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // @todo: persist
    //
    self::$ApplicationMode = NULL;
    self::$ready = FALSE;
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    $ApplicationMode = self::ready();
    if (!$ApplicationMode) {
      //
      // Create instance of application mode
      //
      $ApplicationMode = new AblePolecat_Mode_Application();
      
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_Application::wakeup();
      if (isset($Environment)) {
        $ApplicationMode->Environment = $Environment;
      }
      else {
        throw new AblePolecat_Environment_Exception('Failed to load Able Polecat application environment.',
          AblePolecat_Error::BOOT_SEQ_VIOLATION);
      }
      
      //
      // wakeup() completed successfully
      //
      self::$ApplicationMode = $ApplicationMode;
      self::$ready = TRUE;
    }
    return self::$ApplicationMode;
  }
}