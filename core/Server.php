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

/**
 * Most current version is loaded from conf file. These are defaults.
 */
define('ABLE_POLECAT_VERSION_NAME', 'DEV-0.3.3');
define('ABLE_POLECAT_VERSION_MAJOR', '0');
define('ABLE_POLECAT_VERSION_MINOR', '3');
define('ABLE_POLECAT_VERSION_REVISION', '3');

/**
 * Request query string parameter.
 */
define('ABLE_POLECAT_BOOT_DIRECTIVE', 'mode');

/**
 * Root directory of the entire Able Polecat core project.
 */
if (!defined('ABLE_POLECAT_ROOT')) {
  $ABLE_POLECAT_ROOT = dirname(__DIR__);
  define('ABLE_POLECAT_ROOT', $ABLE_POLECAT_ROOT);
}

/**
 * Location of Able Polecat core class library.
 */
if (!defined('ABLE_POLECAT_CORE')) {
  $ABLE_POLECAT_CORE = __DIR__;
  define('ABLE_POLECAT_CORE', $ABLE_POLECAT_CORE);
}

/**
 * Location of directory with host-specific system-wide configuration file(s).
 */
if (!defined('ABLE_POLECAT_ETC')) {
  $ABLE_POLECAT_ETC = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'etc';
  define('ABLE_POLECAT_ETC', $ABLE_POLECAT_ETC);
}

/**
 * Variable files directory (e.g. log files).
 */
if (!defined('ABLE_POLECAT_FILES')) {
  $ABLE_POLECAT_FILES = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'files';
  define('ABLE_POLECAT_FILES', $ABLE_POLECAT_FILES);
}

/**
 * Secondary directory hierarchy contains third-party modules, custom pages, services, 
 * utilities, etc.
 */
if (!defined('ABLE_POLECAT_USR')) {
  $ABLE_POLECAT_USR = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'usr';;
  define('ABLE_POLECAT_USR', $ABLE_POLECAT_USR);
}

//
// These are listed in the order they are created in initialize() and bootstrap()
//
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Boot.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Conf', 'Dom.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'ClassRegistry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Bus.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Url.php');

class AblePolecat_Server implements AblePolecat_AccessControl_SubjectInterface {
  
  const UUID              = '603a37e0-5dec-11e3-949a-0800200c9a66';
  const NAME              = 'Able Polecat Server';
  
  const DBNAME            =  'polecat';
  
  //
  // Able Polecat request resource id (passed as parameter in request).
  //
  const REQUEST_PARAM_RESOURCE_ID = 'prid';
  
  //
  // Boot directives (passed as parameters in request).
  //
  const BOOT_MODE         = 'mode';
  
  //
  // Boot modes
  //
  const BOOT_MODE_NORMAL  = 'user';
  const BOOT_MODE_INSTALL = 'install';
  const BOOT_MODE_UPDATE  = 'update';
  
  //
  // Boot sequence
  //
  const BOOT_SEQ_SYS_CONFIG     = 0; // Load system configuration settings from conf file(s).
  const BOOT_SEQ_SERVER_MODE    = 1; // Start server environment and set boot mode.
  const BOOT_SEQ_DATABASE       = 2; // Establish a connection to the core/application database.
  const BOOT_SEQ_CLASS_REGISTRY = 3; // Load registry of supported class and interface definitions.
  const BOOT_SEQ_DEFAULT_LOG    = 4; // Start the database logging feature.
  const BOOT_SEQ_ACCESS_CONTROL = 5; // Initiate access control for applications and users.
  const BOOT_SEQ_SERVICE_BUS    = 6; // Bring service bus on line.
  const BOOT_SEQ_APPLICATION_MODE = 7; // Load user and third-party code engine.
  const BOOT_SEQ_USER_MODE      = 8; // Start user session and exit bootstrap.
  
  //
  // protection ring 0, Server Mode.
  //
  const RING_ACCESS_CONTROL   = 0;
  const RING_BOOT_MODE        = 0;
  const RING_DATABASE         = 0;
  const RING_DEFAULT_LOG      = 0;
  const RING_CLASS_REGISTRY   = 0;
  const RING_SERVER_MODE      = 0;
  const RING_SERVICE_BUS      = 0;
  const RING_SYS_CONFIG       = 0;
  
  const NAME_ACCESS_CONTROL   = 'access control';
  const NAME_BOOT_MODE        = 'boot mode';
  const NAME_DATABASE         = 'database';
  const NAME_DATABASES        = 'databases';
  const NAME_DEFAULT_LOG      = 'default log';
  const NAME_CLASS_REGISTRY   = 'class registry';
  const NAME_SERVER_MODE      = 'server mode';
  const NAME_SERVICE_BUS      = 'service bus';
  const NAME_SYS_CONFIG       = 'system configuration';

  //
  // Protection ring 1, Application Mode.
  //
  const RING_APPLICATION_MODE = 1;
  
  const NAME_APPLICATION_MODE = 'application mode';
  
  //
  // Protection ring 2, User Mode.
  //
  const RING_USER_MODE        = 2;
  
  const NAME_USER_MODE        = 'user mode';
  
  //
  // System environment variable names
  //
  const SYSVAR_CORE_VERSION   = 'coreVersion';
  const SYSVAR_CORE_CLASSES   = 'coreClasses';
  const SYSVAR_CORE_DATABASE  = 'coreDatabase';
  const SYSVAR_CORE_INTERFACES = 'coreInterfaces';
  
  /**
   * @var int An incremental marker for continually checking state of boostrap procedure.
   */
  private static $boot_check = 0;
  
  /**
   * @var AblePolecat_Message_ResponseInterface Only response sent by this script.
   */
  private static $Response = NULL;
    
  /**
   * @var AblePolecat_Server Singleton instance.
   */
  private static $Server = NULL;
  
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
  private $Resources = NULL;
  
  /**
   * @var AblePolecat_AccessControl_Agent_Server
   */
  private $Agent;
  
  /**
   * @var AblePolecat_Log_Boot Saves log messages in a file until db log is available.
   */
  private $BootLog;
  
  /**
   * @var Array Information about the state of the application database.
   */
  private $db_state;
  
  /**
   * @var string Version number from server config settings file.
   */
  private $version;
  
  /********************************************************************************
   * Bootstrap functions
   ********************************************************************************/
  
  /**
   * Bootstrap procedure for Able Polecat.
   *
   * @param int $mode 0 = Server mode only, 1 = mode 0 + Application mode, 2 = mode 1 + User mode
   */
  public static function bootstrap($mode = self::RING_USER_MODE) {
    
    //
    // AblePolecat_Server implements Singelton design pattern.
    //
    if (!isset(self::$Server)) {
      //
      // Create instance of Singleton.
      //
      self::$Server = new self();
      
      //
      // System configuration settings needed for bootstrap.
      //
      self::$Server->bootSysConfig();
      
      //
      // Server environment configuration settings
      //
      self::$Server->bootServerMode();
      
      //
      // Application database
      //
      self::$Server->bootDatabase();
      
      //
      // Class registry
      //
      self::$Server->bootClassRegistry();
      
      //
      // Application log
      //
      self::$Server->bootLog();
      

      //
      // Access control service
      //
      self::$Server->bootAccessControl();
      
      //
      // ESB - service bus
      //
      self::$Server->bootServiceBus();
      
      //
      // Third-party modules
      //
      self::$Server->bootApplicationMode();
      
      //
      // User session and access control management
      //
      self::$Server->bootUserMode();
            
      //
      // Register some other core classes.
      //
      // self::getClassRegistry()->registerLoadableClass($class_name,
        // $path_to_include,
        // $create_method
      // );

      //
      // Close the boot log file
      //
      if (isset(self::$Server->BootLog)) {
        self::$Server->BootLog = NULL;
      }
    }
  }
  
  /**
   * Load system-wide configuration settings needed to boot server from file(s).
   */
  protected function bootSysConfig() {
    
    $errmsg = '';
    
    try {
      //
      // Merge system-wide configuration settings from one or more XML doc(s).
      //
      $SysConfig = AblePolecat_Conf_Dom::wakeup($this->Agent);
      
      // Set access control constraints.
      //
      $SysConfig->setPermission($this->Agent, AblePolecat_AccessControl_Constraint_Read::getId());
      $SysConfig->setPermission($this->Agent, AblePolecat_AccessControl_Constraint_Write::getId());
    
      self::setResource(self::RING_SYS_CONFIG, self::NAME_SYS_CONFIG, $SysConfig);
      self::log(AblePolecat_LogInterface::BOOT, 'Wakeup System Configuration - OK');
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup System Configuration - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to load system configuration settings. " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
   
  /**
   * Server Mode - manages core environment configuration settings.
   */
  protected function bootServerMode() {
    
    $errmsg = '';
    
    try {
      //
      // Server Mode - handles configuration of core class library and error/exception handling
      //
      $ServerMode = AblePolecat_Mode_Server::wakeup($this->Agent);
      self::setResource(self::RING_SERVER_MODE, self::NAME_SERVER_MODE, $ServerMode);
      
      //
      // Get version number from server conf
      //
      $this->setVersion($ServerMode->getEnvironment()->getVariable($this->Agent, self::SYSVAR_CORE_VERSION));
      
      //
      // Set the bootstrap Server Mode
      // (AblePolecat_Mode_Server::wakeup() return type is determined by boot directive.)
      //
      $BootMode = self::BOOT_MODE_NORMAL;
      switch (get_class($ServerMode)) {
        default:
          break;
        case 'AblePolecat_Mode_Server_Install':
          $BootMode = self::BOOT_MODE_INSTALL;
          break;
        case 'AblePolecat_Mode_Server_Update':
          $BootMode = self::BOOT_MODE_UPDATE;
          break;
      }
      self::setResource(self::RING_BOOT_MODE, self::NAME_BOOT_MODE, $BootMode);
      self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Server Mode - OK');
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup Server Mode - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to boot server mode. " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * Application database.
   */
  protected function bootDatabase() {
    
    $errmsg = '';
    
    try {
      //
      // Load core database configuration settings.
      //
      $ServerMode = self::getServerMode();
      $this->db_state = $ServerMode->getEnvironment()->getVariable($this->Agent, self::SYSVAR_CORE_DATABASE);
      $this->db_state['connected'] = FALSE;
      if (isset($this->db_state['dsn'])) {
        //
        // Attempt a connection.
        //
        $Database = AblePolecat_Database_Pdo::wakeup($this->Agent);
        $Database->setPermission($this->Agent, AblePolecat_AccessControl_Constraint_Open::getId());
        $DbUrl = AblePolecat_AccessControl_Resource_Locater::create($this->db_state['dsn']);
        $Database->open($this->Agent, $DbUrl);           
        self::setResource(self::RING_DATABASE, self::NAME_DATABASE, $Database);
        $this->db_state['connected'] = TRUE;
        self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Server Mode - OK');
      }
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      !isset($this->db_state['connected']) ? $this->db_state['connected'] = FALSE : NULL;
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup Application Database - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to boot application database. " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * Default application log.
   */
  protected function bootLog() {
    
    $errmsg = '';
    
    try {
      //
      // First choice for logger is database, then CSV
      //
      if (self::$Server->getDatabaseState('connected')) {
        require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Pdo.php')));
        $DefaultLog = AblePolecat_Log_Pdo::wakeup($this->Agent);
        self::setResource(self::RING_DEFAULT_LOG, self::NAME_DEFAULT_LOG, $DefaultLog);
      }
      else {
        require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Csv.php')));
        $DefaultLog = AblePolecat_Log_Csv::wakeup($this->Agent);
        self::setResource(self::RING_DEFAULT_LOG, self::NAME_DEFAULT_LOG, $DefaultLog);
      }
      self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Server Mode - OK');
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup Application Log - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to boot application log. " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * Manages registration and auto-loading of classes.
   */
  protected function bootClassRegistry() {
    
    $errmsg = '';
    
    try {
      //
      // First choice for class registry is database, then XML
      //
      if (self::$Server->getDatabaseState('connected')) {
        require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'ClassRegistry.php')));
        $ClassRegistry = AblePolecat_ClassRegistry::wakeup($this->Agent);
        self::setResource(self::RING_CLASS_REGISTRY, self::NAME_CLASS_REGISTRY, $ClassRegistry);
        self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Class Registry - OK');
      }
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup Class Registry - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to boot class registry. " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * Role-based access control.
   */
  protected function bootAccessControl() {
    
    $errmsg = '';
    
    try {
      //
      // Wakeup access control service with current session.
      //
      if (self::$Server->getDatabaseState('connected')) {
        $AccessControl = AblePolecat_AccessControl::wakeup();
        self::setResource(self::RING_ACCESS_CONTROL, self::NAME_ACCESS_CONTROL, $AccessControl);
        self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Access Control - OK');
      }
      else {
        self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Access Control (NO DB) - SKIP');
      }
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup Access Control - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to . " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * Service Bus - Manages message queues, transactions and web service client connections.
   */
  protected function bootServiceBus() {
    
    $errmsg = '';
    
    try {
      //
      // Service Bus
      //
      if (self::$Server->getDatabaseState('connected')) {
        $ServiceBus = AblePolecat_Service_Bus::wakeup();
        self::setResource(self::RING_SERVICE_BUS, self::NAME_SERVICE_BUS, $ServiceBus);
        self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Service Bus - OK');
      }
      else {
        self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Service Bus (NO DB) - SKIP');
      }
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup Service Bus - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to boot service bus. " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * Application Mode - handles third party code (modules)
   */
  protected function bootApplicationMode() {
    
    $errmsg = '';
    
    try {
      //
      // Protection ring 1, Application mode.
      //
      self::$Server->Resources[self::RING_APPLICATION_MODE] = array();
      
      //
      // Wakeup access control agent.
      //
      require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Application.php')));
      $AppAgent = AblePolecat_AccessControl_Agent_Application::wakeup($this);
      
      //
      // Wakeup Application Mode
      //
      require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Application.php')));
      $ApplicationMode = AblePolecat_Mode_Application::wakeup($AppAgent);
      self::setResource(self::RING_APPLICATION_MODE, self::NAME_APPLICATION_MODE, $ApplicationMode);
      
      //
      // Load application resources from contributed modules.
      //
      $ApplicationMode->loadRegisteredResources();
      
      //
      // Register services and service client classes with service bus.
      //
      self::getServiceBus()->registerServiceInitiators();
      
      //
      // Put success messages at bottom in case something up there chokes
      //
      self::log(AblePolecat_LogInterface::BOOT, 'Wakeup Application Mode - OK');
      self::log(AblePolecat_LogInterface::BOOT, 'Load third-party resources - OK');
      self::log(AblePolecat_LogInterface::BOOT, 'Register third-party clients - OK');
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup Application Mode - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to boot Application Mode. " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * User Mode - manages user session, agent and access control roles
   */
  protected function bootUserMode() {
    
    $errmsg = '';
    
    try {      
      //
      // User mode.
      //
      self::$Server->Resources[self::RING_USER_MODE] = array();
      
      //
      // Wakeup access control agent.
      //
      require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'User.php')));
      $UserAgent = AblePolecat_AccessControl_Agent_User::wakeup(self::getAccessControl()->getSession());
      
      require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'User.php')));
      $UserMode = AblePolecat_Mode_User::wakeup($UserAgent);
      self::setResource(self::RING_USER_MODE, self::NAME_USER_MODE, $UserMode);
      self::log(AblePolecat_LogInterface::BOOT, 'Wakeup User Mode - OK');
      $this->bootCheck();
    }
    catch(Exception $Exception) {
      self::log(AblePolecat_LogInterface::ERROR, 'Wakeup User Mode - FAIL');
      self::log(AblePolecat_LogInterface::ERROR, $Exception->getMessage());
      $errmsg .= " Failed to . " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * Checks bootstrap procedure after each sub-routine and takes action accordingly.
   */
  protected function bootCheck() {
    
    $Go = FALSE;
    
    switch (self::$boot_check) {
      default:
        break;
      case self::BOOT_SEQ_SYS_CONFIG:
        //
        // Check state of system configuration document.
        //
        if (isset($this->Resources[self::RING_SYS_CONFIG][self::NAME_SYS_CONFIG])) {
          $Go = is_a($this->Resources[self::RING_SYS_CONFIG][self::NAME_SYS_CONFIG], 
            'AblePolecat_ConfInterface'
          );
        }
        break;
      case self::BOOT_SEQ_SERVER_MODE:
        //
        // Check state of system configuration document.
        //
        if (isset($this->Resources[self::RING_SERVER_MODE][self::NAME_SERVER_MODE])) {
          $Go = is_a($this->Resources[self::RING_SERVER_MODE][self::NAME_SERVER_MODE], 
            'AblePolecat_Mode_Server'
          ) &&
          isset($this->Resources[self::RING_BOOT_MODE][self::NAME_BOOT_MODE]);
        }
        break;
      case self::BOOT_SEQ_DATABASE:
        $Go = self::$Server->getDatabaseState('connected');
        break;
      case self::BOOT_SEQ_CLASS_REGISTRY:
        // Load registry of supported class and interface definitions.
        break;
      case self::BOOT_SEQ_DEFAULT_LOG:
        // Start the database logging feature.
        break;
      case self::BOOT_SEQ_ACCESS_CONTROL:
        // Initiate access control for applications and users.
        break;
      case self::BOOT_SEQ_SERVICE_BUS:
        // Bring service bus on line.
        break;
      case self::BOOT_SEQ_APPLICATION_MODE:
        // Load user and third-party code engine.
        break;
      case self::BOOT_SEQ_USER_MODE:
        // Start user session and exit bootstrap.
        break;
    }
    
    if ($Go) {
      //
      // Advance to next step.
      //
      self::$boot_check++;
    }
    else {
      $statusPageContent = "<p>Able Polecat encountered a problem during the bootstrap process and shut down.</p>";
      switch (self::$boot_check) {
        default:
          break;
        case self::BOOT_SEQ_SYS_CONFIG:
          $statusPageContent .= "<p>The system configuration file(s) appear to be missing or corrupted.</p>";
          break;
        case self::BOOT_SEQ_SERVER_MODE:
          $statusPageContent .= "<p>The server environment settings failed to load properly.</p>";
          break;
        case self::BOOT_SEQ_DATABASE:
          $statusPageContent .= "<p>A connection with the core (application) database could not be made.</p>";
          break;
        case self::BOOT_SEQ_CLASS_REGISTRY:
          // Load registry of supported class and interface definitions.
          break;
        case self::BOOT_SEQ_DEFAULT_LOG:
          // Start the database logging feature.
          break;
        case self::BOOT_SEQ_ACCESS_CONTROL:
          // Initiate access control for applications and users.
          break;
        case self::BOOT_SEQ_SERVICE_BUS:
          // Bring service bus on line.
          break;
        case self::BOOT_SEQ_APPLICATION_MODE:
          // Load user and third-party code engine.
          break;
        case self::BOOT_SEQ_USER_MODE:
          // Start user session and exit bootstrap.
          break;
      }
      $statusPageContent .= "<p>Please consult the <a href=\"https://github.com/kkuhrman/AblePolecat/wiki/Getting-Started\">
        installation instructions</a> for further information about setting up Able Polecat to run on your web server.</p>";
      self::sendStatusPageResponse($statusPageContent);
    }
  }
  
  /********************************************************************************
   * Server Resource functions
   ********************************************************************************/
  
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
    
    $errmsg = '';
    $Server = NULL;
    
    try {
      $Server = AblePolecat_Server::getServer();
      if (isset($Server->Resources[$ring]) && is_array($Server->Resources[$ring])) {
        if (!isset($Server->Resources[$ring][$name])) {
          $Server->Resources[$ring][$name] = $resource;
        }
      }
    }
    catch (AblePolecat_Server_Exception $Exception) {
      $errmsg .= $Exception->getMessage();
    }
    if (!isset($Server->Resources[$ring][$name])) {
      $errmsg .= " Able Polecat Server rejected attempt to cache resource given by $name at protection ring $ring.";
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
  
  /**
   * Sets version information from core configuration file.
   */
  protected function setVersion($version = NULL) {
    
    if (isset($version['name']) &&
        isset($version['major']) &&
        isset($version['minor']) &&
        isset($version['revision'])) {
        $this->version = array();
      $this->version['name'] = $version['name'];
      $this->version['major'] = $version['major'];
      $this->version['minor'] = $version['minor'];
      $this->version['revision'] = $version['revision'];
    }
    else {
      $this->version = array(
        'name' => ABLE_POLECAT_VERSION_NAME,
        'major' => ABLE_POLECAT_VERSION_MAJOR,
        'minor' => ABLE_POLECAT_VERSION_MINOR,
        'revision' => ABLE_POLECAT_VERSION_REVISION,
      );
    }
  }
  
  /**
   * Retrieves resource given by $name in protection ring given by $ring.
   *
   * @param int $ring Ring assignment.
   * @param string $name Internal name of resource.
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return mixed The cached resource or NULL.
   *
   * @throw Exception if no resource stored at given location.
   */
  protected static function getResource($ring, $name, $safe = TRUE) {
    
    $resource = NULL;
    $errmsg = '';
    
    try {
      $Server = AblePolecat_Server::getServer();
      if (isset($Server->Resources[$ring]) && isset($Server->Resources[$ring][$name])) {
        $resource = $Server->Resources[$ring][$name];
      }
    }
    catch (AblePolecat_Server_Exception $Exception) {
      $errmsg .= $Exception->getMessage();
    }

    if (!isset($resource) && $safe) {
      $errmsg = " Attempt to retrieve Able Polecat Server resource given by $name at protection ring $ring failed. ";
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
    return $resource;
  }
  
  /**
   * Get access to server access control resource.
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_AccessControl.
   */
  public static function getAccessControl($safe = TRUE) {
    return self::getResource(self::RING_ACCESS_CONTROL, self::NAME_ACCESS_CONTROL, $safe);
  }
  
  /**
   * Get access to application mode resource.
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_Mode_Application.
   */
  public static function getApplicationMode($safe = TRUE) {
    return self::getResource(self::RING_APPLICATION_MODE, self::NAME_APPLICATION_MODE, $safe);
  }
  
  /**
   * @return string dev | qa | user.
   */
  public static function getBootMode() {
    return self::getResource(self::RING_BOOT_MODE, self::NAME_BOOT_MODE, FALSE);
  }
  
  /**
   * Get access to class registry resource.
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_ClassRegistry.
   */
  public static function getClassRegistry($safe = TRUE) {
    return self::getResource(self::RING_CLASS_REGISTRY, self::NAME_CLASS_REGISTRY, $safe);
  }
  
  /**
   * Get access to application database.
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_DatabaseInterface.
   */
  public static function getDatabase($safe = TRUE) {
    return self::getResource(self::RING_DATABASE, self::NAME_DATABASE, $safe);
  }
  
  /**
   * Get access to default log resource.
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_LogInterface.
   */
  public static function getDefaultLog($safe = TRUE) {
  
    $Log = self::getResource(self::RING_DEFAULT_LOG, self::NAME_DEFAULT_LOG, $safe);
    if (!isset($Log) && isset(self::$Server->BootLog)) {
      $Log = self::$Server->BootLog;
    }
    else if ($safe) {
      throw new AblePolecat_Server_Exception(
        'Message sent to Able Polecat server log but no log facility is available.',
        AblePolecat_Error::ACCESS_INVALID_OBJECT
      );
    }
    return $Log;
  }
  
  /**
   * Get access to server mode resource.
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_Mode_ServerAbstract or NULL.
   */
  public static function getServerMode($safe = TRUE) {
    return self::getResource(self::RING_SERVER_MODE, self::NAME_SERVER_MODE, $safe);
  }
  
  /**
   * Get access to service bus.
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_Service_BusInterface or NULL.
   */
  public static function getServiceBus($safe = TRUE) {
    return self::getResource(self::RING_SERVICE_BUS, self::NAME_SERVICE_BUS, $safe);
  }
  
  /**
   * Get system-wide configuration settings (XML/DOM).
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_ConfInterface or NULL.
   */
  public static function getSysConfig($safe = TRUE) {
    return self::getResource(self::RING_SYS_CONFIG, self::NAME_SYS_CONFIG, $safe);
  }
  
  /**
   * Get access to user mode resource.
   *
   * @param bool $safe If TRUE will not return NULL, rather throw exception.
   *
   * @return AblePolecat_Mode_User or NULL.
   */
  public static function getUserMode($safe = TRUE) {
    return self::getResource(self::RING_USER_MODE, self::NAME_USER_MODE, $safe);
  }
  
  /********************************************************************************
   * Logging, error handling, redirects and other server behaviour
   ********************************************************************************/
  
  /**
   * Log a message to standard/default log (file).
   * 
   * @param string $severity error | warning | status | info | debug.
   * @param mixed  $message Message body.
   * @param int    $code Error code.
   */
  protected static function writeToDefaultLog($severity, $message, $code = NULL) {
    
    $DefaultLog = self::getDefaultLog(FALSE);
    if (isset($DefaultLog)) {
      switch ($severity) {
        default:
          $DefaultLog->logStatusMessage($message);
          break;
        case AblePolecat_LogInterface::BOOT:
          //
          // These are only sent to boot log
          //
          if (isset(self::$Server->BootLog)) {
            self::$Server->BootLog->logStatusMessage($message);
          }
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
  }
  
  /**
   * Handle critical environment errors depending on runtime context.
   */
  public static function handleCriticalError($error_number, $error_message = NULL) {
    
    //
    // Set a default error message if not provided.
    //
    !isset($error_message) ? $error_message = ABLE_POLECAT_EXCEPTION_MSG($error_number) : NULL;
    
    //
    // Create an HTTP response with error info in body.
    //
    self::$Response = AblePolecat_Message_Response::create(500);
    self::$Response->body = AblePolecat_Message_Response::BODY_DOCTYPE_XML;
    self::$Response->body .= sprintf("<able_polecat_server><error_number>%d</error_number><error_message>%s</error_message></able_polecat_server>", 
      $error_number,
      $error_message
    );
    
    //
    // Attempt to log message.
    //
    self::log(AblePolecat_LogInterface::ERROR, $error_message, $error_number);
    
    //
    // Shut down server and send response.
    //
    self::shutdown();
  }
  
  /**
   * Log a message to standard/default log (file).
   * 
   * @param string $severity error | warning | status | info | debug.
   * @param mixed  $message Message body.
   * @param int    $code Error code.
   */
  public static function log($severity, $message, $code = NULL) {

    //
    // Default log.
    //
    $type = AblePolecat_LogInterface::INFO;
    switch ($severity) {
      default:
        break;
      case AblePolecat_LogInterface::BOOT:
        //
        // These are only sent to boot log
        //
        if (isset(self::$Server->BootLog)) {
          self::$Server->BootLog->logStatusMessage($message);
        }
        break;
      case AblePolecat_LogInterface::STATUS:
      case AblePolecat_LogInterface::WARNING:
      case AblePolecat_LogInterface::ERROR:
      case AblePolecat_LogInterface::DEBUG:
        $type = $severity;
        break;
    }
    self::writeToDefaultLog($type, $message, $code);
    
    if (isset(self::$Server)) {
      //
      // Application (contributed) logs
      //
      $ApplicationMode = self::getResource(self::RING_APPLICATION_MODE, self::NAME_APPLICATION_MODE, FALSE);
      if (isset($ApplicationMode)) {
        $ApplicationMode->log($severity, $message, $code);
      }
    }
  }
  
  /**
   * Handles a request with an unidentified resource.
   *
   * @param string $resource_id Optional.
   */
  public static function redirect($resource_id = NULL) {  
    //
    // Create a default response.
    //
    self::$Response = AblePolecat_Message_Response::create(302);
    self::$Response->body = AblePolecat_Message_Response::BODY_DOCTYPE_XML;
    self::$Response->body .= "<able_polecat>";
    
    //
    // Able Polecat version.
    //
    self::$Response->body .= AblePolecat_Server::getVersion(TRUE, 'XML');
    
    //
    // Application database state.
    //
    self::$Server->getDatabaseState('connected') ? $dbstate = 'connected' : $dbstate = 'not connected';
    self::$Response->body .= "<dbstate>$dbstate</dbstate>";
    
    self::$Response->body .= "</able_polecat>";
  }
  
  /**
   * Main point of entry for all Able Polecat page and service requests.
   *
   */
  public static function routeRequest() {
    
    //
    // Default error message.
    //
    $error_msg = 'Failed to route request.';
    
    try {
      //
      // Bootstrap routine
      //
      self::bootstrap();
      
      //
      // Build request.
      //
      $Request = NULL;
      isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
      switch ($method) {
        default:
          break;
        case 'GET':
          $Request = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Get');
          break;
        case 'POST':
          $Request = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Post');
          break;
        case 'PUT':
          $Request = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Put');
          break;
        case 'DELETE':
          $Request = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Message_Request_Delete');
          break;
      }
      if (isset($Request)) {
        //
        // Id of requested service/page.
        //
        $resource_id = self::getRequestVariable(self::REQUEST_PARAM_RESOURCE_ID);
        if (isset($resource_id)) {
          $Request->setResource($resource_id);
          $error_msg .= " resource id:  $resource_id";
          
          //
          // @todo: get request HEAD
          // @todo: get request BODY
          //
          
          //
          // Dispatch the request.
          //
          $Agent = AblePolecat_Server::getUserMode()->getUserAgent();
          self::$Response = AblePolecat_Server::getServiceBus()->dispatch($Agent, $Request);
        }
        else {
          //
          // Get default response.
          //
          $error_msg .= " resource id:  none";
          AblePolecat_Server::redirect();
        }
      }
      else {
        $error_msg .= " resource id:  none";
      }
    }
    catch(Exception $Exception) {
      $error_msg .= ' ' . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::HTTP_REQUEST_ROUTE_FAIL, $error_msg);
    }
    
    //
    // shut down and send response
    //
    AblePolecat_Server::shutdown();
  }
  
  /**
   * Shut down Able Polecat server and send HTTP response.
   */
  public static function shutdown() {
  
    //
    // Default shut down code - OK
    //
    $exitmsg = 0;
    
    if (!isset(self::$Response)) {
      //
      // This should never happen, even if script fails before response can be created.
      // Exceptions/Errors should always be caught and passed to handleCriticalError.
      // This is here for debugging mainly
      //
      self::sendStatusPageResponse('Able Polecat encountered an unexpected error and must shut down. Check log(s) for details.');
    }
    else {
      self::$Response->send();
    }
      
    exit($exitmsg);
  }
   
  /********************************************************************************
   * Server state helper functions
   ********************************************************************************/
  
  /**
   * Get information about state of application database at boot time.
   *
   * @param mixed $param If set, a particular parameter.
   *
   * @return mixed Array containing all state data, or value of given parameter or FALSE.
   */
  protected function getDatabaseState($param = NULL) {
    
    $state = FALSE;
    if (isset($this->db_state)) {
      if (isset($param) && isset($this->db_state[$param])) {
        $state = $this->db_state[$param];
      }
      else {
        $state = $this->db_state;
      }
    }
    return $state;
  }
  
  /**
   * Returns value of given bootstrap directive from request.
   *
   * @param string $directive Name of directive.
   *
   * @return mixed value of directive.
   */
  public static function getBootDirective($directive) {
    //
    // This code checks query string for a boot mode parameter named 'run'.
    // If passed, and mode is authorized for client, server will boot in
    // requested mode. If parameter is not passed, but mode is stored in a 
    // cookie, server will boot in cookie mode. Otherwise, the server will 
    // boot in normal mode.
    //
    $value = AblePolecat_Server::getRequestVariable(ABLE_POLECAT_BOOT_DIRECTIVE);
    if (!isset($value)) {
      //
      // If runtime context was saved in a cookie, use that until agent
      // explicitly unsets with run=user or cookie expires.
      //
      if (isset($_COOKIE[ABLE_POLECAT_BOOT_DIRECTIVE])) {
        $data = unserialize($_COOKIE[ABLE_POLECAT_BOOT_DIRECTIVE]);
        isset($data[$directive]) ? $value = $data[$directive] : NULL;
      }
    }
    return $value;
  }
  
  /**
   * Helper function returns given $_REQUEST variable.
   *
   * @param string $var Name of requested query string variable.
   *
   * @return mixed Value of requested variable or NULL.
   */
  public static function getRequestVariable($var) {
    $value = NULL;
    if (isset($var) && isset($_REQUEST[$var])) {
      $value = $_REQUEST[$var];
    }
    return $value;
  }
  
  /**
   * Get version number of server/core.
   */
  public static function getVersion($as_str = TRUE, $doc_type = 'XML') {
    
    $version = NULL;
    
    if (isset(self::$Server->version)) {
      if ($as_str) {
        switch ($doc_type) {
          default:
            $version = sprintf("Version %s.%s.%s (%s)",
              self::$Server->version['major'],
              self::$Server->version['minor'],
              self::$Server->version['revision'],
              self::$Server->version['name']
            );
            break;
          case 'XML':
            $version = sprintf("<polecat_version name=\"%s\"><major>%s</major><minor>%s</minor><revision>%s</revision></polecat_version>",
              self::$Server->version['name'],
              strval(self::$Server->version['major']),
              strval(self::$Server->version['minor']),
              strval(self::$Server->version['revision'])
            );
            break;
          //
          // @todo: case 'JSON':
          //
        }
      }
      else {
        $version = self::$Server->version;
      }
    }
    else {
      if ($as_str) {
        $version = sprintf("Version %s.%s.%s (%s)",
          ABLE_POLECAT_VERSION_MAJOR,
          ABLE_POLECAT_VERSION_MINOR,
          ABLE_POLECAT_VERSION_REVISION,
          ABLE_POLECAT_VERSION_NAME
        );
      }
      else {
        $version = array(
          'name' => ABLE_POLECAT_VERSION_NAME,
          'major' => ABLE_POLECAT_VERSION_MAJOR,
          'minor' => ABLE_POLECAT_VERSION_MINOR,
          'revision' => ABLE_POLECAT_VERSION_REVISION,
        );
      }
    }
    return $version;
  }
  
  /********************************************************************************
   * Sends HTML status page.
   *
   * Able Polecat is not designed to intended to serve up web pages. However, there
   * will be instances where it is necessary to display *something* more than XML
   * without style rules; for example, something goes wrong during an installation 
   * or update.
   *
   * @param mixed $content Content of status page.
   * 
   ********************************************************************************/
   
  public static function sendStatusPageResponse($content = NULL) {
    
    $caption = "Able Polecat &copy; Project";
    $version = "Core " . self::getVersion(TRUE, NULL);
    $swnotice = "The Able Polecat Core is free software released 'as is' under a BSD II license 
      (see <a href=\"https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md\">license</a> for more detail.)";
    $copyright = "Copyright &copy; 2008-2013 <a href=\"http://www.abledistributors.com\" target=\"new\">
      Able Distributors Inc.</a> All rights reserved.";
    
    self::$Response = AblePolecat_Message_Response::create(
      200, 
      array(
        AblePolecat_Message_ResponseInterface::HEAD_CONTENT_TYPE_HTML
      )
    );
    self::$Response->body = "<!DOCTYPE html>\n<html>\n<head>\n<title>Able Polecat | Status</title>\n</head>\n";
    self::$Response->body .= "<body>\n<div id=\"container\" style=\"left:12px;width:520px; style=\"font-family: \"Lucida Console\", \"Verdana\", Sans-serif; border-style: solid; border-color: black;\">\n";
    
    self::$Response->body .= "<div id=\"caption\" style=\"position:relative; padding:4px; opacity:0.8;height:64px;background-color:grey; border-style: solid; border-color: white;\">\n";
    self::$Response->body .= "<h2>$caption</h2>\n</div>\n";

    self::$Response->body .= "<div id=\"version\" style=\"position:relative; padding:4px; background-color:palegoldenrod; border-style: solid; border-color: white;\">";
    self::$Response->body .= "<h3>$version</h3>\n</div>\n";
    
    if (isset($content)) {
        self::$Response->body .= "<div id=\"content\" style=\"position:relative; padding:4px; background-color: lightpink; border-style: solid; border-color: white;\">\n";
        self::$Response->body .= "<p>$content</p>\n</div>\n";
    }
    
    self::$Response->body .= "<div id=\"notice\" style=\"position:relative; padding:4px; opacity:0.8;height:100px;background-color:grey; border-style: solid; border-color: white;\">\n";
    self::$Response->body .= "<p><small>$swnotice</small></p><p>$copyright</p></div>\n";
     
    self::$Response->body .= "</div>\n</body>\n</html>";
    
    //
    // Shutdown and send response.
    //
    self::shutdown();
  }
   
  /********************************************************************************
   * Constructor and article identification
   ********************************************************************************/
     
  /**
   * Initialize resources in protection ring '0' (e.g. kernel).
   */
  protected function initialize() {
    
    //
    // state of the application database.
    //
    $this->db_state = NULL;
    
    //
    // 'Kernel' resources container.
    //
    $this->Resources[self::RING_SERVER_MODE] = array();
    
    //
    // Only caches messages in memory until end of script
    //
    $this->BootLog = AblePolecat_Log_Boot::wakeup();
    
    //
    // Access control agent (super user).
    //
    $this->Agent = AblePolecat_AccessControl_Agent_Server::wakeup($this);
    
    //
    // Until loaded from conf, use defaults.
    // 
    $this->setVersion();
    
    self::log(AblePolecat_LogInterface::BOOT, 'Server object initialized.');
  }
  
  /**
   * Return unique, system-wide identifier for security resource.
   *
   * @return string Resource identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security resource.
   *
   * @return string Resource name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /**
   * Get instance of singleton.
   */
  protected static function getServer() {
    
    if (!isset(self::$Server)) {
      //
      // throw exception - prevent use of null object to access property/method
      //
      throw new AblePolecat_Server_Exception(
        'Able Polecat server is not initialized.',
        AblePolecat_Error::ACCESS_INVALID_OBJECT
      );
    }
    return self::$Server;
  }
  
  final protected function __construct() {
    
    //
    // Turn on output buffering.
    //
    ob_start();
    
    //
    // Not ready until after initialize().
    //
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    //
    // Flush output buffer.
    //
    ob_end_flush();
  }
}

/**
 * Exceptions thrown by Able Polecat Server.
 */
class AblePolecat_Server_Exception extends AblePolecat_Exception {
}