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
  
}

abstract class AblePolecat_EnvironmentAbstract implements AblePolecat_EnvironmentInterface {
  
  /**
   * Class registration constants.
   */
  const CLASS_REG_PATH    = 'path';
  const CLASS_REG_METHOD  = 'method';
  
  /**
   * @var Array Registry of classes which can be loaded.
   */
  private $m_loadable_classes;
  
  /**
   * @var Array Registry of loggers.
   */
  private $m_registered_loggers;
  
  /**
   * @var Logger is used to send messages to stdout and stderr.
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
    $this->m_registered_loggers = array();
    $this->m_Logger = NULL;
    $this->m_Agents = array();
    $this->m_Config = NULL;
    $this->m_AppDb = NULL;
    $this->m_ServiceBus = NULL;
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
        throw new AblePolecat_Environment_Exception("Invalid registration for $class_name: constructor", 
          AblePolecat_Environment_Exception::ERROR_ENV_BOOTSTRAP_CLASS_REG);
      }
    }
    else {
      throw new AblePolecat_Environment_Exception("Invalid include path for $class_name: include file path", 
        AblePolecat_Environment_Exception::ERROR_ENV_BOOTSTRAP_PATH);
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
    // $this->logStatusMessage('agent id', $agent_id);
    // $this->logStatusMessage('agents', $this->m_Agents);
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
      throw new AblePolecat_Environment_Exception("Failure to return a current environment object.", 
        AblePolecat_Environment_Exception::ERROR_ENV_GET_CURRENT);
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
    if (isset($Agent) && $Config->open($Agent, $Url)) {
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
   * Set the service bus.
   *
   * @param AblePolecat_Service_Bus $Bus.
   *
   */
  public function setServiceBus(AblePolecat_Service_Bus $Bus) {
    $this->m_ServiceBus = $Bus;
  }
  
  /**
   * Registers logger classes for later loading based settings.php defs.
   */
  public function registerLoggerClasses() {
    //
    // @todo: loggers will be defined in global conf file and module conf files
    //
    // add so: $this->m_registered_loggers[$logger_id] = $class_name;
  }
  
  /**
   * Loads preregistered logger classes.
   */
  public function loadLoggerClasses() {
    $this->m_Logger = array();
    foreach($this->m_registered_loggers as $logger_id => $class_name) {
      if ($this->isLoadable($class_name)) {
        $this->m_Logger[$logger_id] = $this->loadClass($class_name);
        if (!isset($this->m_Logger[$logger_id])) {
          throw new AblePolecat_Environment_Exception("Failed to load logger class $class_name.", 
            AblePolecat_Environment_Exception::ERROR_ENV_BOOTSTRAP_LOGGER);
        }
      }
    }
  }
  
  /**
   * Log a status message to all logger streams.
   * 
   * @param mixed $msg... variable set of arguments.
   *
   */
  public function logStatusMessage($msg = NULL) {
    !is_string($msg) ? $message = serialize($msg) : $message = $msg;
    if (isset($this->m_Logger) && is_array($this->m_Logger)) {
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
    !is_string($msg) ? $message = serialize($msg) : $message = $msg;
    if (isset($this->m_Logger) && is_array($this->m_Logger)) {
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
    if (isset($this->m_Logger) && is_array($this->m_Logger)) {
      foreach($this->m_Logger as $key => $logger) {
        $logger->logErrorMessage($message);
      }
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
   * Sub classes must implement bootstrap(), which will return instance of class.
   */
  final protected function __construct() {
    $this->initialize();
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
        case AblePolecat_LogInterface::WARNING:
          $Environment->logStatusMessage($message, $data);
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
  
  /**
   * Error codes for environment bootstrap process.
   */
  const ERROR_ENV_BOOTSTRAP_PATH          = 0x01; // Invalid boot file path encountered.
  const ERROR_ENV_BOOTSTRAP_CLASS_REG     = 0x02; // Invalid loadable class registration.
  const ERROR_ENV_BOOTSTRAP_LOGGER        = 0x04; // Failure to set logger.
  const ERROR_ENV_BOOTSTRAP_AGENT         = 0x08; // Failure to initialize application access control agent.
  const ERROR_ENV_BOOTSTRAP_CONFIG        = 0x10; // Failure to access/set application configuration.
  const ERROR_ENV_BOOTSTRAP_SESSION       = 0x20; // Failure to start session.
  const ERROR_ENV_BOOTSTRAP_DB            = 0x40; // Failure to open application database.
  const ERROR_ENV_BOOTSTRAP_BUS           = 0x80; // Failure to bring service bus online.
  const ERROR_ENV_GET_CURRENT             = 0x100; // Failure to return a current environment object.
  const ERROR_ENV_GET_MEMBER              = 0x200; // Failure to return a environment member object.
}
