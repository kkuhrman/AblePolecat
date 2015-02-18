<?php
/**
 * @file      polecat/core/Environment/Application.php
 * @brief     Environment for Able Polecat Application Mode.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment.php')));

class AblePolecat_Environment_Application extends AblePolecat_EnvironmentAbstract {
  
  const UUID = 'df5e0c10-5f4d-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Application Environment';
    
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
        
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Application(s) environment initialized.');
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
   * Register a class library and its dependencies.
   *
   * @param string $classLibrariesConfFilePath
   * @param Array $ClassLibraryRegistrations Optional container
   *
   * @return Array Container with zero or more AblePolecat_Registry_Entry_ClassLibrary.
   */
  public function registerClassLibraries($classLibrariesConfFilePath, &$ClassLibraryRegistrations) {
    
    //
    // Containers for variables form any conf files.
    //
    // if (isset($ClassLibraryRegistrations)) {
      // $ClassLibraryRegistrations = array();
    // }
    if (!is_array($ClassLibraryRegistrations)) {
      $msg = sprintf("%s - second parameter must be Array or NULL. %s passed.", 
        __METHOD__, 
        AblePolecat_Data::getDataTypeName($ClassLibraryRegistrations)
      );
      throw new AblePolecat_Environment_Exception($msg, AblePolecat_Error::BOOTSTRAP_CONFIG);
    }
    
    if (file_exists($classLibrariesConfFilePath)) {
      $Conf = new DOMDocument();
      $Conf->load($classLibrariesConfFilePath);
      
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
            }
          }
          
          //
          // If library full path is not defined, try default.
          //
          if (!isset($ClassLibraryRegistration->classLibraryDirectory)) {
            $ClassLibraryRegistration->classLibraryDirectory = AblePolecat_Server_Paths::getFullPath('libs') .
              DIRECTORY_SEPARATOR . $ClassLibraryRegistration->classLibraryName;
          }
          if (AblePolecat_Server_Paths::verifyDirectory($ClassLibraryRegistration->classLibraryDirectory)) {
            //
            // Append path to PHP INI paths
            //
            set_include_path(get_include_path() . PATH_SEPARATOR . $ClassLibraryRegistration->classLibraryDirectory);
            
            //
            // Add path to Able Polecat configurable paths.
            //
            AblePolecat_Server_Paths::setFullPath($ClassLibraryRegistration->classLibraryId, $ClassLibraryRegistration->classLibraryDirectory);
            
            //
            // Check if MOD or library has any dependencies and register those, too.
            //
            $depLibsConfFilePath = implode(DIRECTORY_SEPARATOR, 
              array(
                $ClassLibraryRegistration->classLibraryDirectory, 
                'etc', 
                'polecat', 
                'conf', 
                self::CONF_FILENAME_LIBS
              )
            );
            
            //
            // Necessary to avoid infinite recursion...
            //
            if ($classLibrariesConfFilePath != $depLibsConfFilePath) {
              $this->registerClassLibraries($depLibsConfFilePath, $ClassLibraryRegistrations);
            }
            
            //
            // if there is a paths.config file, include that, too...
            //
            $depLibPathConfFilePath = implode(DIRECTORY_SEPARATOR, 
              array(
                $ClassLibraryRegistration->classLibraryDirectory, 
                'etc', 
                'polecat', 
                'conf', 
                'path.config'
              )
            );
            if (file_exists($depLibPathConfFilePath) && (ABLE_POLECAT_ROOT_PATH_CONF_FILE_PATH != $depLibPathConfFilePath)) {
              include_once($depLibPathConfFilePath);
            }
          }
          $ClassLibraryRegistrations[] = $ClassLibraryRegistration;
        }
      }
    }
    return $ClassLibraryRegistrations;
  }
   
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    parent::initialize();
  }
}