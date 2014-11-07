<?php
/**
 * @file      polecat/core/Environment/Application.php
 * @brief     Environment for Able Polecat Application Mode.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'ClassLibrary.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment.php')));

class AblePolecat_Environment_Application extends AblePolecat_EnvironmentAbstract {
  
  const UUID = 'df5e0c10-5f4d-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Application Environment';
  
  /**
   * System environment variable names
   */
  const SYSVAR_CORE_CLASSLIBS    = 'classLibraries';
  
  /**
   * Configuration file constants.
   */
  const CONF_FILENAME_LIBS        = 'libs.xml';
  
  /**
   * @var AblePolecat_Environment_Server Singleton instance.
   */
  private static $Environment = NULL;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
  
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
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
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
   * @return AblePolecat_Environment_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Environment)) {
      try {
        //
        // Initialize singleton instance.
        //
        self::$Environment = new AblePolecat_Environment_Application($Subject);
        
        //
        // Containers for variables form any conf files.
        //
        $ClassLibraryRegistrations = array();
        
        //
        // Get application settings from configuration file.
        //
        $confPath = implode(DIRECTORY_SEPARATOR, 
          array(
            AblePolecat_Server_Paths::getFullPath('conf'),
            self::CONF_FILENAME_LIBS
          )
        );
        if (file_exists($confPath)) {
          $Conf = new DOMDocument();
          $Conf->load($confPath);
          
          //
          // Class library registrations.
          //
          $LibsNodeList = AblePolecat_Dom::getElementsByTagName($Conf, 'classLibrary');
          foreach($LibsNodeList as $key => $Node) {
            //
            // Register each class library flagged with use="1"
            //
            if ($Node->getAttribute('use')) {
              $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::create();
              $ClassLibraryRegistration->classLibraryName = $Node->getAttribute('name');
              $ClassLibraryRegistration->classLibraryId = $Node->getAttribute('id');
              $ClassLibraryRegistration->classLibraryType = $Node->getAttribute('type');
              foreach($Node->childNodes as $key => $childNode) {
                if($childNode->nodeName == 'fullPath') {
                  $ClassLibraryRegistration->classLibraryDirectory = $childNode->nodeValue;
                  //
                  // Append path to PHP INI paths
                  //
                  set_include_path(get_include_path() . PATH_SEPARATOR . $ClassLibraryRegistration->classLibraryDirectory);
                  
                  //
                  // Add path to Able Polecat configurable paths.
                  //
                  AblePolecat_Server_Paths::setFullPath($ClassLibraryRegistration->classLibraryId, $ClassLibraryRegistration->classLibraryDirectory);
                }
              }
              $ClassLibraryRegistrations[] = $ClassLibraryRegistration;
            }
          }
        }
        
        //
        // Initialize system environment variables from conf file.
        //
        self::$Environment->setVariable(
          $Subject,
          self::SYSVAR_CORE_CLASSLIBS,
          $ClassLibraryRegistrations
        );
        AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'Application(s) environment initialized.');
      }
      catch (Exception $Exception) {
        throw new AblePolecat_Environment_Exception("Failure to initialize application(s) environment. " . $Exception->getMessage(), 
          AblePolecat_Error::BOOTSTRAP_CONFIG);
      }
    }
    return self::$Environment;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    parent::initialize();
  }
}