<?php
/**
 * @file: Server.php
 * Environment for Able Polecat Server Mode.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Environment', 'Conf.php')));

class AblePolecat_Environment_Server extends AblePolecat_Environment_ConfAbstract {
  
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
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // Runtime context may be saved in cookie for local development and testing.
    //
    $this->setServerModeCookie(AblePolecat_Server::getBootMode());
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Environment_Server or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    $Environment = self::$Environment;
    if (!isset($Environment)) {
      //
      // Create environment object.
      //
      $Environment = new AblePolecat_Environment_Server();
      
      //
      // Initialize server access control.
      //
      $Agent = $Environment->loadAccessControlAgent(
        'AblePolecat_AccessControl_Agent_Server',
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Agent', 'Server.php')),
        'wakeup'
      );

      //
      // Load application configuration settings.
      //    
      // Load AblePolecat_Storage_File_Conf, which must implement 
      // AblePolecat_AccessControl_ResourceInterface. Will use agent 
      // created in #4 to gain access to this. If file does not exist 
      // initialization routine will create it with default settings.
      //
      AblePolecat_Server::getClassRegistry()->registerLoadableClass(
        'AblePolecat_Conf_Server',
        ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Server.php',
        'touch'
      );
      $Config = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Conf_Server');
      $ConfigUrl = AblePolecat_Conf_Server::getResourceLocater();
      if (isset($Config) && $ConfigUrl) {
        //
        // Grant open permission on config file to agent.
        //
        $Config->setPermission($Agent, AblePolecat_AccessControl_Constraint_Open::getId());
        $Config->setPermission($Agent, AblePolecat_AccessControl_Constraint_Read::getId());
        
        //
        // Set configuration file/path.
        //
        $Environment->setConf($Config, $ConfigUrl);
      }
      else {
        throw new AblePolecat_Environment_Exception("Failure to access/set application configuration.", 
          AblePolecat_Error::BOOTSTRAP_CONFIG);
      }

      //
      // Initialize singleton instance.
      //
      self::$Environment = $Environment;
    }
    return self::$Environment;
  }
}
