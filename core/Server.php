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
 * Secondary directory hierarchy contains third-party modules, custom pages, services, 
 * utilities, etc.
 */
if (!defined('ABLE_POLECAT_USR')) {
  $ABLE_POLECAT_USR = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'usr';;
  define('ABLE_POLECAT_USR', $ABLE_POLECAT_USR);
}

/**
 * Variable files directory (e.g. log files).
 */
if (!defined('ABLE_POLECAT_VAR')) {
  $ABLE_POLECAT_VAR = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'var';
  define('ABLE_POLECAT_VAR', $ABLE_POLECAT_VAR);
}

//
// These are listed in the order they are created in initialize() and bootstrap()
//
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));
require_once(ABLE_POLECAT_CORE. DIRECTORY_SEPARATOR . 'Database.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'ClassRegistry.php')));

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Boot.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Csv.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Bus.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Url.php');

class AblePolecat_Server implements AblePolecat_AccessControl_SubjectInterface {
  
  const UUID              = '603a37e0-5dec-11e3-949a-0800200c9a66';
  const NAME              = 'Able Polecat Server';
  
  const DBNAME            =  'polecat';
  
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
  
  /**
   * functions called in bootstrap().
   */
  
  /**
   * boot server mode
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
      // var_dump($versionConf);
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
   * boot function template
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
          isset($dbattr['mode']) ? $dbmode = $dbattr['mode']->__toString() : $dbmode = $BootMode;
          isset($dbattr['use']) ? $dbuse = intval($dbattr['use']->__toString()) : $dbuse = 0;        
          if (($dbmode = $BootMode) && $dbuse) {
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
   * boot function template
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
   * boot function template
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
   * boot function template
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
   * boot function template
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
      // Wakeup Application Mode
      //
      require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Application.php')));
      $ApplicationMode = AblePolecat_Mode_Application::wakeup(self::getAccessControl()->getSession());
      self::setResource(self::RING_APPLICATION_MODE, self::NAME_APPLICATION_MODE, $ApplicationMode);
      
      //
      // Load application resources from contributed modules.
      //
      $ApplicationMode->loadRegisteredResources();
      
      //
      // Register client classes from contributed contributed modules with service bus.
      //
      self::getServiceBus()->registerClients();
      
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
      require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'User.php')));
      $UserMode = AblePolecat_Mode_User::wakeup(self::getAccessControl()->getSession());
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
  
  /**
   * boot function template
   */
  protected function bootTemplate() {
    
    $errmsg = '';
    
    try {
      //
      // @todo: initialize and cache resource
      //
    }
    catch(Exception $Exception) {
      $errmsg .= " Failed to . " . $Exception->getMessage();
      self::handleCriticalError(AblePolecat_Error::BOOT_SEQ_VIOLATION, $errmsg);
    }
  }
    
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
   * Bootstrap procedure for Able Polecat.
   */
  public static function bootstrap() {
    
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
          AblePolecat_Server::redirect(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_VAR, 'tpl', 'main.tpl')));
      }
      
      //
      // Application log
      //
      self::$Server->bootLog();
      
      //
      // Class registry
      //
      self::$Server->bootClassRegistry();
      
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
        self::$Server->bootApplicationMode();
        
        //
        // User session and access control management
        //
        self::$Server->bootUserMode();
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
    $value = AblePolecat_Server::getRequestVariable('mode');
    if (!isset($value)) {
      //
      // If runtime context was saved in a cookie, use that until agent
      // explicitly unsets with run=user or cookie expires.
      //
      if (isset($_COOKIE['ABLE_POLECAT_RUNTIME'])) {
        $data = unserialize($_COOKIE['ABLE_POLECAT_RUNTIME']);
        isset($data[$directive]) ? $value = $data[$directive] : NULL;
      }
    }
    return $value;
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
  
  /**
   * Get version number of server/core.
   */
  public static function getVersion($as_str = TRUE) {
    
    $version = NULL;
    
    if (isset(self::$Server)) {
      if ($as_str) {
        $version = sprintf("Version %s.%s.%s (%s)",
          self::$Server->version['major'],
          self::$Server->version['minor'],
          self::$Server->version['revision'],
          self::$Server->version['name']
        );
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
   * Handle critical environment errors depending on runtime context.
   */
  public static function handleCriticalError($error_number, $error_message = NULL) {
  
    !isset($error_message) ? $error_message = ABLE_POLECAT_EXCEPTION_MSG($error_number) : NULL;
    // require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Browser.php')));
    // AblePolecat_Log_Browser::dumpBacktrace($error_message);
    self::shutdown('error', $error_message, $error_number);
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
   * Handles requests to redirect a request from a web browser.
   *
   * @param string $location An absolute URL or a preset Able Polecat page/template.
   * @param bool $replace Parameter in PHP header() NIU
   * @param int $http_response_code Parameter in PHP header() NIU
   */
  public static function redirect($location = '', $replace = TRUE, $http_response_code = 302) {
    
    //
    // @todo: this is merely a stub, obviously not a solution
    //
    $Locater = AblePolecat_AccessControl_Resource_Locater::create($location);
    $output = file_get_contents($Locater);
    if ($output) {
      $dbstate = 'The connection to the application database is ';
      switch (self::$Server->getDatabaseState('connected')) {
        default:
          $dbstate .= 'active.';
          break;
        case FALSE:
          $dbstate .= 'NOT active.';
          break;
      }
      $output = str_replace(
        array(
          '{POLECAT_VERSION}',
          '{POLECAT_DBSTATE}',
        ),
        array(
          '<em>' . AblePolecat_Server::getVersion() . '</em>', 
          '<em>' . $dbstate . '</em>', 
        ),
        $output
      );
      echo $output;
    }
    self::shutdown();
  }
  
  /**
   * Force shut down of  Able Polecat.
   * 
   * @param string $severity error | warning | status | info | debug.
   * @param mixed  $message Message body.
   * @param int    $code Error code.
   */
  public static function shutdown($severity = NULL, $message = NULL, $code = NULL) {
  
  //
  // Default shutdown code - OK
  //
  $exitmsg = 0;
  
  //
  // If no error information provided, bypass logging
  //
  if (isset($severity) && isset($message)) {
    isset($code) ? $codetxt = sprintf("code %d", $code) : $codetxt = "no code";
      $exitmsg = sprintf("Able Polecat was forced to shutdown. %s condition.  %s. %s",
        $severity, 
        $message, 
        $codetxt
      );
      self::writeToDefaultLog($severity, $exitmsg, $code);
      
      //
      // Close the boot log file if it's open
      //
      if (isset(self::$Server->BootLog)) {
        self::$Server->BootLog->logStatusMessage($message);
        self::$Server->BootLog->sleep();
        self::$Server->BootLog = NULL;
      }
    }
    
    //
    // shut down procedures
    //
    try {
    }
    catch(Exception $Exception) {
    }
      
    exit($exitmsg);
  }
  
  /**
   * Similar to DOM ready() but for Able Polecat core system.
   *
   * @return AblePolecat_Server or FALSE.
   */
  // public static function ready() {
    // return self::$Server;
  // }
  
  final protected function __construct() {
    
    //
    // Not ready until after initialize().
    //
    $this->initialize();
  }
}

/**
 * Exceptions thrown by Able Polecat Server.
 */
class AblePolecat_Server_Exception extends AblePolecat_Exception {
}