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
        self::$Environment = new AblePolecat_Environment_Server($Subject);
        
        //
        // Merge system-wide configuration settings from one or more XML doc(s).
        //
        $SysConfig = AblePolecat_Conf_Dom::wakeup($Subject);
        
        //
        // Initialize system environment variables from conf file.
        //
        self::$Environment->setVariable(
          $Subject,
          AblePolecat_Server::SYSVAR_CORE_VERSION,
          $SysConfig->getCoreVersion()
        );
        self::$Environment->setVariable(
          $Subject,
          AblePolecat_Server::SYSVAR_CORE_DATABASE,
          $SysConfig->getCoreDatabaseConf()
        );
      }
      catch (Exception $Exception) {
        throw new AblePolecat_Environment_Exception("Failure to access/set application configuration. " . $Exception->getMessage(), 
          AblePolecat_Error::BOOTSTRAP_CONFIG);
      }
    }
    return self::$Environment;
  }
}
