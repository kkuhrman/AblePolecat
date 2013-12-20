<?php
/**
 * @file: Server.php
 * Environment for Able Polecat Server Mode.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Conf', 'Dom.php')));

class AblePolecat_Environment_Server extends AblePolecat_EnvironmentAbstract {
  
  const UUID = '318df280-5def-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Server Environment';
  
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
      if (isset($_COOKIE[ABLE_POLECAT_BOOT_DIRECTIVE])) {
        //
        // Compare current cookie setting to parameter
        //
        $data = unserialize($_COOKIE[ABLE_POLECAT_BOOT_DIRECTIVE]);
        isset($data['context']) ? $stored_serverMode = $data['context'] : NULL;
        if ($serverMode != $stored_serverMode) {
          //
          // Setting changed, first expire cookie
          //
          setcookie(ABLE_POLECAT_BOOT_DIRECTIVE, '', time() - 3600);
        }
      }
      $data = array('context' => $serverMode);
      setcookie(ABLE_POLECAT_BOOT_DIRECTIVE, serialize($data), time() + 3600);    
    }
    else if (isset($_COOKIE[ABLE_POLECAT_BOOT_DIRECTIVE])) {
      //
      // Expire any runtime context cookie
      //
      setcookie(ABLE_POLECAT_BOOT_DIRECTIVE, '', time() - 3600);
    }
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
   * Return common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
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
    $this->setServerModeCookie(AblePolecat_Server::getBootDirective(AblePolecat_Server::BOOT_MODE));
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Environment_Server or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Environment)) {
      try {
        //
        // Initialize singleton instance.
        //
        self::$Environment = new AblePolecat_Environment_Server();
        
        //
        // Get system-wide configuration from server.
        //
        $Conf = AblePolecat_Server::getSysConfig();
        
        //
        // Initialize system environment variables from conf file.
        //
        self::$Environment->setVariable(
          $Subject,
          AblePolecat_Server::SYSVAR_CORE_VERSION,
          $Conf->getCoreVersion()
        );
        self::$Environment->setVariable(
          $Subject,
          AblePolecat_Server::SYSVAR_CORE_DATABASE,
          $Conf->getCoreDatabaseConf()
        );
      }
      catch (Exception $Exception) {
        throw new AblePolecat_Environment_Exception("Failure to access/set application configuration. " . $Exception->getMessage(), 
          AblePolecat_Error::BOOTSTRAP_CONFIG);
      }
      
      // $Config = AblePolecat_Conf_Server::touch();
      // $ConfigUrl = AblePolecat_Conf_Server::getResourceLocater();
      // if (isset($Config) && $ConfigUrl) {
        // $Config->setPermission($Subject, AblePolecat_AccessControl_Constraint_Open::getId());
        // $Config->setPermission($Subject, AblePolecat_AccessControl_Constraint_Read::getId());
        
        // self::$Environment->setConf($Config, $ConfigUrl);
        
        // $paths = self::$Environment->getConf('paths');
        // foreach($paths->path as $key => $path) {
          // $pathAttributes = $path->attributes();
          // if (isset($pathAttributes['name'])) {
            // AblePolecat_Server_Paths::setFullPath($pathAttributes['name']->__toString(), $path->__toString());
          // }
        // }
        
        // AblePolecat_Server_Paths::verifyConfDirs();
      // }
      // else {       
      // }
    }
    return self::$Environment;
  }
}
