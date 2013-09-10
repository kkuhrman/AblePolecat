<?php
/**
 * @file: Application.php
 * Base class for Application modes (most protected).
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

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Mode.php');
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
    $this->Resources = array(
      'AblePolecat_LogInterface' => array(),
      'AblePolecat_Service_ClientInterface' => array(),
    );
    
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
      AblePolecat_Server::handleCriticalError(ABLE_POLECAT_EXCEPTION_UNSUPPORTED_INTERFACE, 
        "Attempt to retrieve Able Polecat application resource of type $interface, module $module failed.");
    }
    return $resource;
  }
  
  /**
   * Stores an application resource.
   *
   * @param int $interface Type of resource.
   * @param string $module Name of module.
   * @param mixed $resource The resource to cache.
   *
   * @throw Exception if ring is not intialized.
   */
  protected function setResource($module, $resource) {
    $storedType = NULL;
    foreach($this->Resources as $interfaceType => $interfaceResources) {
      if (is_a($resource, $interfaceType)) {
        if (!isset($this->Resources[$interfaceType][$module])) {
          $this->Resources[$interfaceType][$module] = $resource;
          $storedType = $interfaceType;
          break;
        }
      }
    }
    if (!isset($storedType)) {
      $msg = sprintf("Able Polecat rejected attempt to store application resource for module %s. Interface type %s is not supported.",
        $module,
        get_class($resource)
      );
      AblePolecat_Server::handleCriticalError(ABLE_POLECAT_EXCEPTION_UNSUPPORTED_INTERFACE, $msg);
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
    
    $logs = $this->getResource('AblePolecat_LogInterface', self::RESOURCE_ALL, FALSE);
    if (isset($logs)) {
      foreach($logs as $modName => $log) {
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
  
  /**
   * Initialize and return object implementing AblePolecat_Mode.
   */
  public static function wakeup() {
    
    $ApplicationMode = self::ready();
    if (!$ApplicationMode) {
      //
      // Create instance of application mode
      //
      $ApplicationMode = new AblePolecat_Mode_Application();
      
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_Application::load();
      if (isset($Environment)) {
        $ApplicationMode->Environment = $Environment;
      }
      else {
        throw new AblePolecat_Environment_Exception('Failed to load Able Polecat application environment.',
          ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION);
      }
      
      //
      // Load registered modules
      //
      foreach($Environment->getRegisteredModules() as $modName => $modReg) {
        $modLoadClasses = $modReg['classes'];
        foreach($modLoadClasses as $key => $className) {
          $class = AblePolecat_Server::getClassRegistry()->loadClass($className);
          $ApplicationMode->setResource($modName, $class);
        }
        AblePolecat_Server::log(AblePolecat_LogInterface::STATUS, 
        "Loaded contributed module $modName.");
      }
      
      //
      // wakeup() completed successfully
      //
      self::$ApplicationMode = $ApplicationMode;
      self::$ready = TRUE;
    }
    return self::$ApplicationMode;
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
   * Persist state prior to going out of scope.
   */
  public function sleep() {
  }
}