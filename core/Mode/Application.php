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
 
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Mode.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Application.php')));

class AblePolecat_Mode_Application extends AblePolecat_ModeAbstract {

  /**
   * Constants.
   */
  const UUID = 'b306fe20-5f4c-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Application Mode';
  const RESOURCE_ALL = 'all';
  
  /**
   * @var AblePolecat_Mode_ApplicationAbstract Concrete ApplicationMode instance.
   */
  protected static $ApplicationMode;
    
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
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
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
  
    if (isset(self::$ApplicationMode)) {
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
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // @todo: persist
    //
    self::$ApplicationMode = NULL;
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$ApplicationMode)) {
      //
      // Create instance of application mode
      //
      self::$ApplicationMode = new AblePolecat_Mode_Application();
      
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_Application::wakeup();
      if (isset($Environment)) {
        self::$ApplicationMode->Environment = $Environment;
      }
      else {
        throw new AblePolecat_Environment_Exception('Failed to load Able Polecat application environment.',
          AblePolecat_Error::BOOT_SEQ_VIOLATION);
      }
    }
    return self::$ApplicationMode;
  }
  
  protected function loadModuleDatabases() {
    //
    // Load application database for active mode.
    // @todo: core will only use PDO; move custom class loading to application mode
    //
    $databases = array(
        self::NAME_DATABASES => array(),
        'id' => array(),
        'name' => array(),
    );
    
    //
    // @todo???
    //
    // $dbconf = $ServerMode->getEnvironment()->getConf('databases');
    $dbconf = array();
    
    $dbkey = 0;
    foreach($dbconf as $element_name => $db) {
      $dbattr = $db->attributes();
      isset($dbattr['name']) ? $dbname = $dbattr['name']->__toString() : $dbname = NULL;
      isset($dbattr['id']) ? $dbid = $dbattr['id']->__toString() : $dbid = NULL;
      isset($db->modulename) ? $dbmodulename = $db->modulename->__toString() : $dbmodulename = NULL;
      
      if (!isset($dbname) && !isset($dbid)) {
        AblePolecat_Server::log(AblePolecat_LogInterface::WARNING, "Database(s) defined in conf must have either id or name attribute assigned.");
      }
      else {        
        //
        // If module is not core, wait until application mode.
        //
        if (isset($dbmodulename) && ($dbmodulename !== 'core')) {
          isset($db->classname) ? $dbclassname = $db->classname->__toString() : $dbclassname = NULL;
          if (isset($db->classname)) {
            //
            // Register and load the database class.
            //
            AblePolecat_Server::getClassRegistry()->registerLoadableClass($dbclassname, NULL, 'wakeup');
            $Database = AblePolecat_Server::getClassRegistry()->loadClass($dbclassname);
            
            //
            // Open the database connection.
            //
            $Database->setPermission($this->Agent, AblePolecat_AccessControl_Constraint_Open::getId());
            isset($db->dsn) ? $dbdsn = $db->dsn->__toString() : $dbdsn = NULL;
            $DbUrl = AblePolecat_AccessControl_Resource_Locater::create($dbdsn);
            $Database->open($this->Agent, $DbUrl);
            
            //
            // Add the database to server.
            //
            $databases[AblePolecat_Server::NAME_DATABASES][$dbkey] = $Database;
            isset($dbid) ? $databases['id'][$dbid] = $dbkey : NULL;
            isset($dbname) ? $databases['name'][$dbname] = $dbkey : NULL;
          }
          else {
            AblePolecat_Server::log(AblePolecat_LogInterface::WARNING, sprintf("no class name given for database %s in conf.", $dbname));
          }
        }
        else {
          AblePolecat_Server::log(AblePolecat_LogInterface::WARNING, sprintf("no module name given for database %s in conf.", $dbname));
        }
      }
      $dbkey++;
    }
    self::setResource($dbclassname, $dbmodulename, $databases);
  }
  
  /**
   * @param string $id Id or name of registered database.
   *
   * @return AblePolecat_DatabaseInterface.
   */
  public static function getDatabase($id) {
    $Database = NULL;
    // $databases = self::getResource(self::RING_DATABASES, self::NAME_DATABASES);
    $key = NULL;
    if (isset($databases['id'][$id])) {
      $key = $databases['id'][$id];
    }
    else if (isset($databases['name'][$id])) {
      $key = $databases['name'][$id];
    }
    if (isset($databases[AblePolecat_Server::NAME_DATABASES][$key])) {
      $Database = $databases[AblePolecat_Server::NAME_DATABASES][$key];
    }
    else {
      throw new AblePolecat_Server_Exception("Could not access database $id. No such object registered.",
        AblePolecat_Error::ACCESS_INVALID_OBJECT);
    }
    return $Database;
  }
}