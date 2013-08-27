<?php
/**
 * @file: Server.php
 * Environment for Able Polecat Server Mode.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Environment.php');

class AblePolecat_Environment_Server extends AblePolecat_EnvironmentAbstract {
  
  /**
   * @var AblePolecat_Environment_Server Singleton instance.
   */
  private static $Environment = NULL;
  
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    parent::initialize();
  }
  
  /**
   * Helper function uses a cookie to store local dev/test mode settings.
   */
  protected function setServerModeCookie($serverMode) {
    //
    // @todo: Do nothing if agent is not browser.
    //
    if (isset($serverMode)) {
      if (isset($_COOKIE['ABLE_POLECAT_RUNTIME'])) {
        //
        // Compare current cookie setting to parameter
        //
        $data = unserialize($_COOKIE['ABLE_POLECAT_RUNTIME']);
        isset($data['context']) ? $stored_serverMode = $data['context'] : NULL;
        if ($serverMode != $stored_serverMode) {
          //
          // Setting changed, first expire cookie
          //
          setcookie('ABLE_POLECAT_RUNTIME', '', time() - 3600);
        }
      }
      $data = array('context' => $serverMode);
      setcookie('ABLE_POLECAT_RUNTIME', serialize($data), time() + 3600);    
    }
    else if (isset($_COOKIE['ABLE_POLECAT_RUNTIME'])) {
      //
      // Expire any runtime context cookie
      //
      setcookie('ABLE_POLECAT_RUNTIME', '', time() - 3600);
    }
  }
  
  /**
   * Initialize the environment for Able Polecat.
   *
   * @return AblePolecat_Environment_Server.
   */
  public static function load() {
    
    $Environment = self::$Environment;
    if (!isset($Environment)) {
      //
      // Create environment object.
      //
      $Environment = new AblePolecat_Environment_Server();
      
      //
      // Load an instance of the class registry.
      //
      $ClassRegistry = AblePolecat_ClassRegistry::wakeup();
      
      //
      // Initialize application access control.
      //
      // Create instance of AblePolecat_AccessControl_Agent_Server 
      // which must implement AblePolecat_AccessControl_AgentInterface.
      // This should have access to config file, which must implement 
      // AblePolecat_AccessControl_ResourceInterface.
      //
      $ClassRegistry->registerLoadableClass(
        'AblePolecat_AccessControl_Agent_Server', 
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Agent', 'Server.php')),
        'load'
      );
      $Agent = $ClassRegistry->loadClass('AblePolecat_AccessControl_Agent_Server');
      if (isset($Agent)) {
        $Environment->setAgent($Agent);
      }
      else {
        AblePolecat_Server::handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOTSTRAP_AGENT);
      }

      //
      // Load application configuration settings.
      //    
      // Load AblePolecat_Storage_File_Conf, which must implement 
      // AblePolecat_AccessControl_ResourceInterface. Will use agent 
      // created in #4 to gain access to this. If file does not exist 
      // initialization routine will create it with default settings.
      //
      $ClassRegistry->registerLoadableClass(
        'AblePolecat_Conf_Server',
        ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Server.php',
        'touch'
      );
      $Config = $ClassRegistry->loadClass('AblePolecat_Conf_Server');
      if (isset($Config)) {
        //
        // Grant open permission on config file to agent.
        //
        $Config->setPermission($Agent, AblePolecat_AccessControl_Constraint_Open::getId());
        $Config->setPermission($Agent, AblePolecat_AccessControl_Constraint_Read::getId());
        
        //
        // Set configuration file/path.
        //
        $conf_path = NULL;
        $filename = 'server.xml';
        $conf_path = AblePolecat_Conf_Server::getDefaultSubDir() . DIRECTORY_SEPARATOR . $filename;
        $ConfigUrl = AblePolecat_AccessControl_Resource_Locater::create($conf_path, ABLE_POLECAT_CONF_PATH);
        $Environment->setConf($Config, $ConfigUrl);
      }
      else {
        throw new AblePolecat_Environment_Exception("Failure to access/set application configuration.", 
          AblePolecat_Environment_Exception::ERROR_ENV_BOOTSTRAP_CONFIG);
      }

      //
      // Initialize singleton instance.
      //
      self::$Environment = $Environment;
    }
    return self::$Environment;
  }
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep() {
    //
    // Runtime context may be saved in cookie for local development and testing.
    //
    $this->setServerModeCookie(AblePolecat_Server::getBootMode());
  }
}
