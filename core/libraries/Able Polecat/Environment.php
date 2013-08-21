<?php
/**
 * @file: Environment.php
 * Public interface to Able Polecat Environment.
 */

if (!defined('ABLE_POLECAT_PATH')) {
  $able_polecat_path = __DIR__;
  define('ABLE_POLECAT_PATH', $able_polecat_path);
}
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf.php');
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Log.php');

interface AblePolecat_EnvironmentInterface {
  
  /**
   * Initialize the environment for Able Polecat.
   *
   * @return AblePolecat_EnvironmentAbstract sub class.
   */
  public static function bootstrap();
  
  /**
   * Return instance of current, initialized environment.
   *
   * @return object Instance of class which implments AblePolecat_EnvironmentInterface or NULL.
   */
  public static function getCurrent();
  
  /**
   * Check if class can be loaded in current environment.
   * 
   * @param string $class_name The name of class to check for.
   *
   * @return mixed Full include path if class can be loaded, otherwise FALSE.
   */
  public function isLoadable($class_name);
  
  /**
   * Get instance of given class.
   *
   * @param string $class_name The name of class to instantiate.
   * 
   * @return object Instance of given class or NULL.
   */
  public function loadClass($class_name);
  
  /**
   * Log a status message to stdout.
   * 
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function logStatusMessage($msg = NULL);
  
  /**
   * Log a error message to stderr.
   * 
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function logErrorMessage($msg = NULL);
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep();
}

abstract class AblePolecat_EnvironmentAbstract implements AblePolecat_EnvironmentInterface {
  
  /**
   * Class registration constants.
   */
  const CLASS_REG_PATH    = 'path';
  const CLASS_REG_METHOD  = 'method';
  const DEFAULT_LOGGER    = 0;
  
  /**
   * @var Array Registry of classes which can be loaded.
   */
  private $m_loadable_classes;
  
  /**
   * @var Array Registry of contributed module configurations.
   */
  private $m_registered_modules;
  
  /**
   * @var Array Loggers used for saving status, error messages etc.
   */
  private $m_Logger;
  
  /**
   * @var Access control agents.
   */
  private $m_Agents;
  
  /**
   * @var Environment configuration data.
   */
  private $m_Config;
  
  /**
   * @var Application database.
   */
  private $m_AppDb;
  
  /**
   * @var Service bus.
   */
  private $m_ServiceBus;
  
  /**
   * Extends __construct(). 
   * 
   * Sub-classes can override to initialize members prior to bootstrap.
   */
  protected function initialize() {
    $this->m_loadable_classes = array();
    $this->m_registered_modules = array();
    $this->m_Logger = array();
    $this->m_Agents = array();
    $this->m_Config = NULL;
    $this->m_AppDb = NULL;
    $this->m_ServiceBus = NULL;
  }
  
  /**
   * Handle critical environment errors depending on runtime context.
   */
  protected function handleCriticalError($error_number, $error_message = NULL) {
    
    !isset($error_message) ? $error_message = ABLE_POLECAT_EXCEPTION_MSG($error_number) : NULL;
    $runtime_context = self::getRuntimeContext();
    switch ($runtime_context) {
      case ABLE_POLECAT_RUNTIME_DEV:
        //
        // Override SEH - trigger error and die.
        //
        trigger_error($error_message, E_USER_ERROR);
        break;
      default:
        //
        // throw exception
        //
        if (count($this->m_Logger)) {
          throw new AblePolecat_Environment_Exception($error_message, $error_number);
        }
        else {
          trigger_error($error_message, E_USER_ERROR);
        }
        break;
    }
  }
  
  /**
   * Helper function searches given directory for module configuration file.
   *
   * Able Polecat requires contributed modules to have at least one configuration file 
   * with the name module.xml. This file *must* be located in the mods directory. All 
   * other module resources (files, class libraries, etc) can be located elsewhere as 
   * defined in module.xml.
   *
   * Examples of acceptable module configuration file placement:
   * 1. One configuration for all runtime contexts:
   *    [AblePolecat ROOT]/mods/MyModule/conf/module.xml
   * 2. Different configurations for one or more runtime contexts:
   *    [AblePolecat ROOT]/mods/MyModule/conf/dev/module.xml
   *                                      .../qa/module.xml
   *                                      .../use/module.xml
   *
   * @param string $search_directory Name of directory to search.
   * @param string $parent_directory i.e. cd ..
   *
   * @return string Full path name of module configuration file or NULL if not found.
   */
  protected function findModuleConfigurationFile($search_directory, $parent_directory = ABLE_POLECAT_MODS_PATH) {
    
    $conf_path = NULL;
    
    if ($search_directory != "." && $search_directory != "..") {
      $full_path = $parent_directory . DIRECTORY_SEPARATOR . $search_directory;
      if (is_dir($full_path)) {
        $test_path = implode(DIRECTORY_SEPARATOR, array($full_path, 'conf', 'module.xml'));
        if (file_exists($test_path)) {
          //
          // One configuration for all runtime contexts.
          //
          $conf_path = $test_path;
        }
        else {
          $context_dir = $this->getRuntimeContext(TRUE);
          $test_path = implode(DIRECTORY_SEPARATOR, array($full_path, 'conf', $context_dir, 'module.xml'));
          if (file_exists($test_path)) {
            //
            // Configuration specific to runtime context.
            //
            $conf_path = $test_path;
          }
        }
      }
    }
    return $conf_path;
  }
  
  /**
   * Registers path and creation method for loadable class.
   *
   * @param string $class_name The name of class to register.
   * @param string $path Full path of include file.
   * @param string $method Method used for creation (default is __construct()).
   */
  public function registerLoadableClass($class_name, $path, $method = NULL) {
    
    if (is_file($path)) {
      include_once($path);
      $methods = get_class_methods($class_name);
      if (FALSE !== array_search($method, $methods)) {
        !isset($method) ? $method = '__construct' : NULL;
        $this->m_loadable_classes[$class_name] = array(
          self::CLASS_REG_PATH => $path,
          self::CLASS_REG_METHOD => $method,
        );
      }
      else {
        $this->handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOTSTRAP_CLASS_REG,
          "Invalid registration for $class_name: constructor");
      }
    }
    else {
      $this->handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOT_PATH_INVALID,
        "Invalid include path for $class_name: include file path");
    }
  }
  
  /**
   * Load registered contributed modules.
   */
  public function loadModules() {
    foreach($this->m_registered_modules as $modName => $modReg) {
      $modLoadClasses = $modReg['classes'];
      foreach($modLoadClasses as $key => $className) {
        $class = $this->loadClass($className);
        
        //
        // If module class is a logger, add it to environment
        //
        if (is_a($class, 'AblePolecat_LogInterface')) {
          $this->m_Logger[] = $class;
        }
      }
      $this->logStatusMessage("Loaded contributed module $modName.");
    }
  }
  
  /**
   * Registers all contributed modules in mods directory flagged to be registered.
   * @see findModuleConfigurationFile().
   */
  public function registerModules() {
    //
    // Application agent must be assigned already or all shall fail... oh woe!
    //
    $Agent = $this->getAgentById(AblePolecat_AccessControl_Agent_Application::getId());
    if (isset($Agent)) {
      if (count($this->m_registered_modules) === 0) {
        if (file_exists(ABLE_POLECAT_MODS_PATH) && is_dir(ABLE_POLECAT_MODS_PATH)) {
          $h_mods_dir = opendir(ABLE_POLECAT_MODS_PATH);
          if ($h_mods_dir) {
            while (false !== ($current_file = readdir($h_mods_dir))) {
              $module_conf_path = $this->findModuleConfigurationFile($current_file);
              if (isset($module_conf_path)) {
                $ModConfig = $this->loadClass('AblePolecat_Conf_Module');
                if (isset($ModConfig)) {
                  //
                  // Grant open permission on config file to agent.
                  //
                  $ModConfig->setPermission($Agent, AblePolecat_AccessControl_Constraint_Open::getId());
                  $ModConfig->setPermission($Agent, AblePolecat_AccessControl_Constraint_Read::getId());              
                  $ModConfigUrl = AblePolecat_AccessControl_Resource_Locater::create($module_conf_path);
                  $this->registerModule($ModConfig, $ModConfigUrl);
                }
              }
            }
            closedir($h_mods_dir);
          }
        }
        else {
          throw new AblePolecat_Environment_Exception(ABLE_POLECAT_EXCEPTION_MSG(ABLE_POLECAT_EXCEPTION_MODS_PATH_INVALID), 
            ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION);
        }
      }
    }
    else {
      throw new AblePolecat_Environment_Exception('Cannot register modules before application agent is assigned.', 
        ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION);
    }
  }
  
  /**
   * Registers the given contributed module.
   *
   * Configuration is type SimpleXMLElement, elements are SimpleXMLElement or SimpleXMLIterator.
   * Must cast text as string when passing as function parameters. Make sure __toString is invoked.
   * @see: http://us3.php.net/manual/en/simplexml.examples-basic.php
   *
   * @param AblePolecat_Conf_Module $modConfig Module configuration file
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $modPath Full path to contributed module directory.
   */
  public function registerModule(AblePolecat_Conf_Module $modConfig, AblePolecat_AccessControl_Resource_LocaterInterface $modPath) {
    
    $Agent = $this->getAgentById(AblePolecat_AccessControl_Agent_Application::getId());
    if ($modConfig->open($Agent, $modPath)) {
      $modConfSxElement = $modConfig->read($Agent);
      $moduleAttributes = $modConfSxElement->attributes();
      isset($modConfSxElement->classes) ? $moduleClasses = $modConfSxElement->classes : $moduleClasses = array();
      $modLoadClasses = array();
      foreach($moduleClasses as $key => $class) {
        if(isset($class->{'class'})) {
          $classAttributes = $class->{'class'}->attributes();
          if (isset($classAttributes['register']) && intval($classAttributes['register'])) {
            isset($class->{'class'}->classname) ? $className = $class->{'class'}->classname->__toString() : $className = NULL;
            isset($class->{'class'}->filename) ? $fileName = $class->{'class'}->filename->__toString() : $fileName = NULL;
            if(isset($className) && isset($fileName)) {
              //
              // Trim any leading and trailing slashes from relative URL.
              //
              isset($moduleAttributes['fullpath']) ? $moduleFullpath = trim($moduleAttributes['fullpath'], '/') : $moduleFullpath = '';
              isset($fileName) ? $classFullPath = $moduleFullpath . DIRECTORY_SEPARATOR . $fileName : $classFullPath = NULL;
              isset($class->{'class'}->classFactoryMethod) ? $classFactoryMethod = $class->{'class'}->classFactoryMethod->__toString() : $classFactoryMethod = NULL;
              if(isset($classFullPath) && isset($classFactoryMethod)) {
                $this->registerLoadableClass($className, $classFullPath, $classFactoryMethod);
                if (isset($classAttributes['load']) && intval($classAttributes['load'])) {
                  $modLoadClasses[] = $className;
                }
              }
            }
          }
        }
      }
      $moduleName = $moduleAttributes['name']->__toString();
      $this->m_registered_modules[$moduleName] = array(
        'conf' => $modConfig,
        'path' => $modPath,
        'classes' => $modLoadClasses,
      );
      $this->logStatusMessage("Registered contributed module $moduleName.");
    }
    else {
      $path = $modPath->__toString();
      $this->logErrorMessage("Failed to open module configuration file at $path.");
    }
  }
  
  /**
   * Adds given access control agent to environment.
   *
   * @param object Instance of class which implements AblePolecat_AccessControl_AgentInterface.
   */
  public function addAgent(AblePolecat_AccessControl_AgentInterface $Agent) {
    if (!isset($this->m_Agents[$Agent->getId()])) {
      $this->m_Agents[$Agent->getId()] = $Agent;
    }
  }
  
  /**
   * Return an access control agent by its UUID.
   *
   * @param UUID $agent_id The UUID of the requested agent.
   *
   * @return object Instance of class implementing AblePolecat_AccessControl_AgentInterface or NULL.
   */
  public function getAgentById($agent_id) {
    $Agent = NULL;
    if (isset($this->m_Agents[$agent_id])) {
      $Agent = $this->m_Agents[$agent_id];
    }
    return $Agent;
  }
  
  /**
   * Return all access control agents.
   *
   * @return Array Zero or more instances of class implementing AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgents() {
    $agents = $this->m_Agents;
    return $agents;
  }
  
  /**
   * Get environment configuration settings as assoc array.
   *
   * @param string $start Optional offset to start reading from.
   * @param string $end Optional offset to end reading at.
   *
   * @return SimpleXMLElement Environment configuration settings.
   */
  public function getConf($start = NULL, $end = NULL) {
    
    $Conf = array();
    $Agent = $this->getAgentById(AblePolecat_AccessControl_Agent_Application::getId());
    if (isset($Agent) && isset($this->m_Config)) {
      $Conf = $this->m_Config->read($Agent, $start, $end);
    }
    return $Conf;
  }
  
  /**
   * Get environment configuration settings as a resource (file).
   *
   * @return AblePolecat_ConfAbstract.
   */
  public function getConfResource() {
    $Conf = NULL;
    $Agent = $this->getAgentById(AblePolecat_AccessControl_Agent_Application::getId());
    if (isset($Agent) && isset($this->m_Config)) {
      $Conf = $this->m_Config;
    }
    return $Conf;
  }
  
  /**
   * Return instance of current, initialized environment.
   *
   * @return object Instance of class which implments AblePolecat_EnvironmentInterface or NULL.
   */
  public static function getCurrent() {
    
    $Environment = NULL;
    
    if (isset($GLOBALS['ABLE_POLECAT_ENVIRONMENT'])) {
      if (is_a($GLOBALS['ABLE_POLECAT_ENVIRONMENT'], 'AblePolecat_EnvironmentInterface')) {        
        $Environment = $GLOBALS['ABLE_POLECAT_ENVIRONMENT'];
      }
    }
    else if (isset($GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP'])) {
      //
      // This provides access to environment object as it is being initialized.
      //
      if (is_a($GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP'], 'AblePolecat_EnvironmentInterface')) {        
        $Environment = $GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP'];
      }
    }
    if (!isset($Environment)) {
      //
      // Do not use handleCriticalError() in this case.
      // Catching this exception prevents recursive calls on bootstrap().
      //
      throw new AblePolecat_Environment_Exception(ABLE_POLECAT_EXCEPTION_MSG(ABLE_POLECAT_EXCEPTION_GET_CURRENT_ENV), 
        ABLE_POLECAT_EXCEPTION_GET_CURRENT_ENV);
    }
    
    return $Environment;
  }
  
  /**
   * Get connection to the application database.
   *
   * @return resource A PDO database connection or NULL.
   */
  public function getDb() {    
    return $this->m_AppDb;
  }
  
  /**
   * Get handle the service bus.
   *
   * @return AblePolecat_Service_Bus or NULL.
   */
  public function getServiceBus() {
    return $this->m_ServiceBus;
  }
  
  /**
   * Runtime context is one of the following:
   * user - normal operation
   * dev  - check configuration, file syntax, unit tests...
   * qa   - performance monitoring, use case testing...
   *
   * @param bool $as_string If TRUE, will return string value of rtc, otherwise numeric.
   *
   * @return int or string based on value of $as_string.
   */
  public function getRuntimeContext($as_string = FALSE) {
    
    //
    // defaults: ABLE_POLECAT_RUNTIME_DEV < ABLE_POLECAT_RUNTIME_QA < ABLE_POLECAT_RUNTIME_USER
    //
    $runtime_context = ABLE_POLECAT_RUNTIME_USER;
    if (!ABLE_POLECAT_IS_MODE(ABLE_POLECAT_RUNTIME_USER)) {
      if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_RUNTIME_QA)) {
        $runtime_context = ABLE_POLECAT_RUNTIME_QA;
      }
      else if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_RUNTIME_DEV)) {
        $runtime_context = ABLE_POLECAT_RUNTIME_DEV;
      }
    }
    if ($as_string) {
      switch ($runtime_context) {
        case ABLE_POLECAT_RUNTIME_DEV: 
          $runtime_context = 'dev';
          break;
        case ABLE_POLECAT_RUNTIME_QA:
          $runtime_context = 'qa';
          break;
        case ABLE_POLECAT_RUNTIME_USER:
          $runtime_context = 'user';
          break;
      }
    }
    return $runtime_context;
  }
  
  /**
   * Check if class can be loaded in current environment.
   * 
   * @param string $class_name The name of class to check for.
   *
   * @return Array include file path and creation method, otherwise FALSE.
   */
  public function isLoadable($class_name) {
    
    $response = FALSE;
    if (isset($this->m_loadable_classes[$class_name])) {
      $response = $this->m_loadable_classes[$class_name];
    }
    return $response;
  }
  
  /**
   * Get instance of given class.
   *
   * @param string $class_name The name of class to instantiate.
   * 
   * @return object Instance of given class or NULL.
   */
  public function loadClass($class_name) {
    
    $Instance = NULL;
    $info = $this->isLoadable($class_name);
    if (isset($info[self::CLASS_REG_METHOD])) {
      switch ($info[self::CLASS_REG_METHOD]) {
        default:
          $Instance = call_user_func(array($class_name, $info[self::CLASS_REG_METHOD]));
          break;
        case '__construct':
          $Instance = new $class_name;
          break;
      }
    }
    return $Instance;
  }
  
  /**
   * Set environment configuration data.
   *
   * @param AblePolecat_ConfAbstract $Config
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Config file path.
   */
  public function setConf(AblePolecat_ConfAbstract $Config, AblePolecat_AccessControl_Resource_LocaterInterface $Url) {
    $Agent = $this->getAgentById(AblePolecat_AccessControl_Agent_Application::getId());
    //
    // Application configuration file
    //
    if ($Config->open($Agent, $Url)) {
      $this->m_Config = $Config;
    }
  }
  
  /**
   * Set the application database.
   *
   * @param AblePolecat_DbAbstract $Database.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url.
   */
  public function setDb(AblePolecat_DbAbstract $Database, AblePolecat_AccessControl_Resource_LocaterInterface $Url) {
    $Agent = $this->getAgentById(AblePolecat_AccessControl_Agent_Application::getId());
    if (isset($Agent) && $Database->open($Agent, $Url)) {
      $this->m_AppDb = $Database;
    }
  }
  
  /**
   * Set default logger for environment.
   * 
   * @param AblePolecat_LogInterface $Logger.
   */
  public function setDefaultLogger(AblePolecat_LogInterface $Logger) {
    
    if (isset($Logger)) {
      if (isset($this->m_Logger[self::DEFAULT_LOGGER])) {
        //
        // Only replace default logger if different type.
        //
        $className = get_class($Logger);
        if (!is_a($this->m_Logger[self::DEFAULT_LOGGER], $className)) {
          $oldDefaultLogger = $this->m_Logger[self::DEFAULT_LOGGER];
          $this->m_Logger[self::DEFAULT_LOGGER] = $Logger;
          $this->m_Logger[] = $oldDefaultLogger;
        }
      }
      else {
        $this->m_Logger[self::DEFAULT_LOGGER] = $Logger;
      }
    }
    else {
      $this->handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOTSTRAP_LOGGER,
        "Default logger cannot be NULL.");
    }
  }
  
  /**
   * Set the service bus.
   *
   * @param AblePolecat_Service_Bus $Bus.
   *
   */
  public function setServiceBus(AblePolecat_Service_Bus $Bus) {
    $this->m_ServiceBus = $Bus;
  }
  
  /**
   * Log a status message to all logger streams.
   * 
   * @param mixed $msg... variable set of arguments.
   *
   */
  public function logStatusMessage($msg = NULL) {
    if (isset($this->m_Logger) && count($this->m_Logger)) {
      !is_string($msg) ? $message = serialize($msg) : $message = $msg;
      foreach($this->m_Logger as $key => $logger) {
        $logger->logStatusMessage($message);
      }
    }
  }
  
  /**
   * Log a warning to all logger streams.
   * 
   * @param mixed $msg... variable set of arguments.
   *
   */
  public function logWarningMessage($msg = NULL) {
    if (isset($this->m_Logger) && count($this->m_Logger)) {
      !is_string($msg) ? $message = serialize($msg) : $message = $msg;
      foreach($this->m_Logger as $key => $logger) {
        $logger->logWarningMessage($message);
      }
    }
  }
  
  /**
   * Log an error message to all logger streams.
   * 
   * @param mixed $msg... variable set of arguments.
   *
   */
  public function logErrorMessage($msg = NULL) {
    !is_string($msg) ? $message = serialize($msg) : $message = $msg;
    if (isset($this->m_Logger) && count($this->m_Logger)) {
      foreach($this->m_Logger as $key => $logger) {
        $logger->logErrorMessage($message);
      }
    }
    else {
      throw new AblePolecat_Environment_Exception($msg, ABLE_POLECAT_EXCEPTION_UNKNOWN);
    }
  }
  
  /**
   * Dump backtrace to logger with message.
   *
   * Typically only called in a 'panic' situation during testing or development.
   *
   * @param variable $msg Variable list of arguments comprising message.
   */
  public function dumpBacktrace($msg = NULL) {
    !is_string($msg) ? $message = serialize($msg) : $message = $msg;
    if (isset($this->m_Logger) && is_array($this->m_Logger)) {
      foreach($this->m_Logger as $key => $logger) {
        $logger->dumpBacktrace($message);
      }
    }
    panic($message);
  }
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep() {
    $runtime_context = $this->getRuntimeContext();
    switch ($runtime_context) {
      case ABLE_POLECAT_RUNTIME_DEV:
      case ABLE_POLECAT_RUNTIME_QA:
        //
        // Runtime context may be saved in cookie for local development and testing.
        //
        ABLE_POLECAT_RUNTIME_CONTEXT_COOKIE_SET($runtime_context);
        break;
      default:
        //
        // Otherwise, expire any existing runtime context cookie
        //
        ABLE_POLECAT_RUNTIME_CONTEXT_COOKIE_SET(NULL);
        break;
    }
  }
  
  /**
   * Sub classes must implement bootstrap(), which will return instance of class.
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  final public function __destruct() {
    $this->sleep();
  }
}

/**
 * Might sub-class this if child is expected to interface with Environment frequently.
 */
class AblePolecat_Environment_Aware_Object {
  
  /**
   * @return Able Polecat environment.
   */
  public function getEnvironment() {
    $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
    return $Environment;
  }
  
  /**
   * Log message using current environment logger.
   *
   * @param string $message Text of message.
   * @param int $code error | warning | status.
   * @param variable $data Optional data to follow.
   */
  public function logMessage($message, $code = AblePolecat_LogInterface::ERROR, $data = NULL) {
    
    $Environment = $this->getEnvironment();
    if (isset($Environment)) {
      if (isset($data)) {
        $args = func_get_args();
        $message = array_shift($args);
        $code = array_shift($args);
        $data = $args;
      }
      
      switch ($code) {
        default:
          break;
        case AblePolecat_LogInterface::STATUS:
          $Environment->logStatusMessage($message, $data);
          break;
        case AblePolecat_LogInterface::WARNING:
          $Environment->logWarningMessage($message, $data);
          break;
        case AblePolecat_LogInterface::ERROR:
          $Environment->logErrorMessage($message, $data);
          break;
      }
    }
  }
}

/**
 * Exceptions thrown by environment objects.
 */
class AblePolecat_Environment_Exception extends AblePolecat_Exception {
}

