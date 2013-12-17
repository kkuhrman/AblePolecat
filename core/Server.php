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
define('ABLE_POLECAT_VERSION_NAME', 'DEV-0.2.0');
define('ABLE_POLECAT_VERSION_MAJOR', '0');
define('ABLE_POLECAT_VERSION_MINOR', '2');
define('ABLE_POLECAT_VERSION_REVISION', '0');

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
  // protection ring 0, Server Mode.
  //
  const RING_ACCESS_CONTROL   = 0;
  const RING_BOOT_MODE        = 0;
  const RING_DATABASE         = 0;
  const RING_DEFAULT_LOG      = 0;
  const RING_CLASS_REGISTRY   = 0;
  const RING_SERVER_MODE      = 0;
  const RING_SERVICE_BUS      = 0;
  
  const NAME_ACCESS_CONTROL   = 'access control';
  const NAME_BOOT_MODE        = 'boot mode';
  const NAME_DATABASE         = 'database';
  const NAME_DATABASES        = 'databases';
  const NAME_DEFAULT_LOG      = 'default log';
  const NAME_CLASS_REGISTRY   = 'class registry';
  const NAME_SERVER_MODE      = 'server mode';
  const NAME_SERVICE_BUS      = 'service bus';

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
   * @var AblePolecat_Log_Boot A log file for the boot process.
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
      // Server environment configuration settings
      //
      self::$Server->bootServerMode();
      
      //
      // Application database
      //
      self::$Server->bootDatabase();
      
      //
      // No connection to database.
      // Could happen if this is a first time install.
      // But need to rule out any trouble.
      //
      if ((self::$Server->getBootMode() != self::BOOT_MODE_INSTALL) && 
          (self::$Server->getDatabaseState('connected') === FALSE)) {
          //
          // Verify variable file paths exist
          //
          try {
            $logs_path = AblePolecat_Server_Paths::getFullPath('logs');
            AblePolecat_Server_Paths::touch($logs_path);
          }
          catch (AblePolecat_Server_Paths_Exception $Exception) {
            self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $Exception->getMessage());
          }
          AblePolecat_Server::redirect(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_FILES, 'tpl', 'main.tpl')));
      }
      
      //
      // Class registry
      //
      self::$Server->bootClassRegistry();
      
      //
      // Application log
      //
      self::$Server->bootLog();
      
      //
      // Remainder of boot procedure is skipped if this is an install
      //
      if (self::$Server->getBootMode() != self::BOOT_MODE_INSTALL) {
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
        if ($mode >= self::RING_APPLICATION_MODE) {
          self::$Server->bootApplicationMode();
        }
        
        //
        // User session and access control management
        //
        if ($mode >= self::RING_USER_MODE) {
          self::$Server->bootUserMode();
        }
      }
            
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
        self::$Server->BootLog->sleep();
        self::$Server->BootLog = NULL;
      }
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
      $versionConf = $ServerMode->getEnvironment()->getConf('version');
      $attr = $versionConf->attributes();
      isset($attr['name']) ? self::$Server->version['name'] = $attr['name']->__toString() : NULL;
      isset($versionConf->major) ? self::$Server->version['major'] = $versionConf->major->__toString() : NULL;
      isset($versionConf->minor) ? self::$Server->version['minor'] = $versionConf->minor->__toString() : NULL;
      isset($versionConf->revision) ? self::$Server->version['revision'] = $versionConf->revision->__toString() : NULL;
      
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
      $this->BootLog->logStatusMessage('Wakeup Server Mode - OK');
    }
    catch(Exception $Exception) {
      $this->BootLog->logStatusMessage('Wakeup Server Mode - FAIL');
      $this->BootLog->logStatusMessage($Exception->getMessage());
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
      $ServerMode = self::getServerMode();
      
      //
      // Load application database for active mode.
      // @todo: core will only use PDO; move custom class loading to application mode
      //
      $dbconf = $ServerMode->getEnvironment()->getConf('databases');
      foreach($dbconf as $element_name => $db) {
        $dbattr = $db->attributes();
        isset($dbattr['name']) ? $dbname = $dbattr['name']->__toString() : $dbname = NULL;
        isset($dbattr['id']) ? $dbid = $dbattr['id']->__toString() : $dbid = NULL;
        // isset($db->modulename) ? $dbmodulename = $db->modulename->__toString() : $dbmodulename = NULL;
        if (!isset($dbname) && !isset($dbid)) {
          self::log(AblePolecat_LogInterface::WARNING, "Database(s) defined in conf must have either id or name attribute assigned.");
        }
        else {
          //
          // More than one application database can be defined in server conf file. However, only ONE
          // application database can be active per server mode. 
          // If 'mode' attribute is empty, polecat will assume any mode; otherwise, database is defined 
          // for given mode only. 
          // The 'use' attribute indicates that the database should be loaded for the mode(s) given by 
          // the 'mode' attribute. 
          // Polecat will scan database definitions until it finds one with mode/use attributes combined 
          // in such a way that directs it to use the database for the current server mode.
          //
          $BootMode = self::getBootMode();
          if (isset($dbattr['mode'])) {
            //
            // database only defined for given mode
            //
            $dbmode = $dbattr['mode']->__toString();
            if ($dbmode == '') {
              //
              // attribute blank, assume any mode (use current)
              //
              $dbmode = $BootMode;
            }
          }
          else {
            //
            // attribute not defined, assume any mode (use current)
            //
            $dbmode = $BootMode;
          }
          isset($dbattr['use']) ? $dbuse = intval($dbattr['use']->__toString()) : $dbuse = 0;
          
          if (($dbmode == $BootMode) && $dbuse) {
            //
            // DSN
            //
            isset($db->dsn) ? $dbdsn = $db->dsn->__toString() : $dbdsn = NULL;
            
            //
            // Store info about application database.
            //
            $this->db_state = array(
              'name' => $dbname,
              'id' => $dbid,
              'mode' => $dbmode,
              'use' => $dbuse,
              'dsn' => $dbdsn,
              'connected' => FALSE,
            );
            
            //
            // Attempt a connection.
            //
            $Database = AblePolecat_Database_Pdo::wakeup($this->Agent);
            $Database->setPermission($this->Agent, AblePolecat_AccessControl_Constraint_Open::getId());
            $DbUrl = AblePolecat_AccessControl_Resource_Locater::create($dbdsn);
            $Database->open($this->Agent, $DbUrl);           
            self::setResource(self::RING_DATABASE, self::NAME_DATABASE, $Database);
            $this->db_state['connected'] = TRUE;
            $this->BootLog->logStatusMessage('Wakeup Server Mode - OK');
            break;
          }
        }
      }
    }
    catch(Exception $Exception) {
      $this->BootLog->logStatusMessage('Wakeup Application Database - FAIL');
      $this->BootLog->logStatusMessage($Exception->getMessage());
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
      $this->BootLog->logStatusMessage('Wakeup Server Mode - OK');
    }
    catch(Exception $Exception) {
      $this->BootLog->logStatusMessage('Wakeup Application Log - FAIL');
      $this->BootLog->logStatusMessage($Exception->getMessage());
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
        require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'ClassRegistry', 'Pdo.php')));
        $ClassRegistry = AblePolecat_ClassRegistry_Pdo::wakeup($this->Agent);
        self::setResource(self::RING_CLASS_REGISTRY, self::NAME_CLASS_REGISTRY, $ClassRegistry);
      }
      else {
        require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'ClassRegistry', 'Xml.php')));
        $ClassRegistry = AblePolecat_ClassRegistry_Xml::wakeup($this->Agent);
        self::setResource(self::RING_CLASS_REGISTRY, self::NAME_CLASS_REGISTRY, $ClassRegistry);
      }
      $this->BootLog->logStatusMessage('Wakeup Class Registry - OK');
    }
    catch(Exception $Exception) {
      $this->BootLog->logStatusMessage('Wakeup Class Registry - FAIL');
      $this->BootLog->logStatusMessage($Exception->getMessage());
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
        $this->BootLog->logStatusMessage('Wakeup Access Control - OK');
      }
      else {
        $this->BootLog->logStatusMessage('Wakeup Access Control (NO DB) - SKIP');
      }
    }
    catch(Exception $Exception) {
      $this->BootLog->logStatusMessage('Wakeup Access Control - FAIL');
      $this->BootLog->logStatusMessage($Exception->getMessage());
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
        $this->BootLog->logStatusMessage('Wakeup Service Bus - OK');
      }
      else {
        $this->BootLog->logStatusMessage('Wakeup Service Bus (NO DB) - SKIP');
      }
    }
    catch(Exception $Exception) {
      $this->BootLog->logStatusMessage('Wakeup Service Bus - FAIL');
      $this->BootLog->logStatusMessage($Exception->getMessage());
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
      $this->BootLog->logStatusMessage('Wakeup Application Mode - OK');
      $this->BootLog->logStatusMessage('Load third-party resources - OK');
      $this->BootLog->logStatusMessage('Register third-party clients - OK');
    }
    catch(Exception $Exception) {
      $this->BootLog->logStatusMessage('Wakeup Application Mode - FAIL');
      $this->BootLog->logStatusMessage($Exception->getMessage());
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
      $this->BootLog->logStatusMessage('Wakeup User Mode - OK');
    }
    catch(Exception $Exception) {
      $this->BootLog->logStatusMessage('Wakeup User Mode - FAIL');
      $this->BootLog->logStatusMessage($Exception->getMessage());
      $errmsg .= " Failed to . " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
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
      $errmsg .= $errmsg = " Attempt to retrieve Able Polecat Server resource given by $name at protection ring $ring failed. ";
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
    return self::getResource(self::RING_DEFAULT_LOG, self::NAME_DEFAULT_LOG, $safe);
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
    self::writeToDefaultLog('error', $error_message, $error_number);
      
    //
    // Close the boot log file if it's open
    //
    if (isset(self::$Server->BootLog)) {
      self::$Server->BootLog->logStatusMessage($error_message);
      self::$Server->BootLog->sleep();
      self::$Server->BootLog = NULL;
    }
    
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
      var_dump(self::$Server);
      die('Able Polecat encountered an unexpected error and must shut down');
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
    
    if (isset(self::$Server)) {
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
    // Until loaded from conf...
    // 
    $this->version = array(
      'name' => ABLE_POLECAT_VERSION_NAME,
      'major' => ABLE_POLECAT_VERSION_MAJOR,
      'minor' => ABLE_POLECAT_VERSION_MINOR,
      'revision' => ABLE_POLECAT_VERSION_REVISION,
    );
    
    $this->BootLog->logStatusMessage('Server object initialized.');
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