<?php
/**
 * @file      polecat/core/Registry/ClassLibrary.php
 * @brief     Manages registry of third-pary class libraries used by modules.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'ClassLibrary.php')));

class AblePolecat_Registry_ClassLibrary extends AblePolecat_RegistryAbstract {
  
  /**
   * AblePolecat_AccessControl_Article_StaticInterface
   */
  const UUID = '9e5f5eda-b7b0-11e4-a12d-0050569e00a2';
  const NAME = __CLASS__;
  
  /**
   * @var AblePolecat_Registry_ClassLibrary Singleton instance.
   */
  private static $Registry = NULL;
  
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
   * Return Common name.
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
   * @return AblePolecat_Registry_ClassLibrary Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Registry)) {
      try {
        //
        // Create instance of singleton.
        //
        self::$Registry = new AblePolecat_Registry_ClassLibrary($Subject);
        
        if (AblePolecat_Database_Pdo::ready()) {
          //
          // Get project database.
          //
          $CoreDatabase = AblePolecat_Database_Pdo::wakeup($Subject);
          
          //
          // Load [lib]
          //
          $sql = __SQL()->
            select('id', 'name', 'libType', 'libFullPath', 'useLib', 'lastModifiedTime')->
            from('lib')->
            where('`useLib` = 1');
          $QueryResult = $CoreDatabase->query($sql);
          foreach($QueryResult as $key => $Library) {
            $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::create();
            isset($Library['id']) ? $ClassLibraryRegistration->id = $Library['id'] : NULL;
            isset($Library['name']) ? $ClassLibraryRegistration->name = $Library['name'] : NULL;
            
            //
            // Check library full path setting.
            //
            isset($Library['libFullPath']) ? $libFullPath = $Library['libFullPath'] : $libFullPath = NULL;
            if (isset($libFullPath) && AblePolecat_Server_Paths::verifyDirectory($libFullPath)) {
              //
              // Append class library path path to PHP INI paths
              //
              $ClassLibraryRegistration->libFullPath = $libFullPath;
              set_include_path(get_include_path() . PATH_SEPARATOR . $ClassLibraryRegistration->libFullPath);
              
              //
              // Add path to Able Polecat configurable paths.
              //
              AblePolecat_Server_Paths::setFullPath($ClassLibraryRegistration->id, $ClassLibraryRegistration->libFullPath);
              
              //
              // if there is a paths.config file, include that, too...
              //
              $pathConfFilePath = implode(DIRECTORY_SEPARATOR, 
                array(
                  $ClassLibraryRegistration->libFullPath, 
                  'etc', 
                  'polecat', 
                  'conf', 
                  'path.config'
                )
              );
              if (file_exists($pathConfFilePath) && (ABLE_POLECAT_ROOT_PATH_CONF_FILE_PATH != $pathConfFilePath)) {
                include_once($pathConfFilePath);
              }
      
              isset($Library['libType']) ? $ClassLibraryRegistration->libType = $Library['libType'] : NULL;
              isset($Library['useLib']) ? $ClassLibraryRegistration->useLib = $Library['useLib'] : NULL;
              isset($Library['lastModifiedTime']) ? $ClassLibraryRegistration->lastModifiedTime = $Library['lastModifiedTime'] : NULL;
              self::$Registry->addRegistration($ClassLibraryRegistration);
            }
            else {
              AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, 
                sprintf("Invalid path provided for class library %s (%s).", $ClassLibraryRegistration->id, $ClassLibraryRegistration->name)
              );
            }
          }
          AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Class library registry initialized.');
        }
      }
      catch (Exception $Exception) {
        self::$Registry = NULL;
        throw new AblePolecat_Registry_Exception($Exception->getMessage(), AblePolecat_Error::WAKEUP_FAIL);
      }
    }
    return self::$Registry;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Database_InstallerInterface.
   ********************************************************************************/
   
  /**
   * Install class registry on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public static function install(AblePolecat_DatabaseInterface $Database) {
    //
    // Core class library conf file.
    //
    $coreFile = AblePolecat_Mode_Config::getCoreClassLibraryConfFile();
    
    //
    // Get package (core class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($coreFile, 'package');
    $corePackageNode = $Nodes->item(0);
    $coreClassLibraryId = $corePackageNode->getAttribute('id');
    if (isset($corePackageNode)) {
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::create();
      $ClassLibraryRegistration->id = $corePackageNode->getAttribute('id');
      $ClassLibraryRegistration->name = $corePackageNode->getAttribute('name');
      $ClassLibraryRegistration->libType = strtolower($corePackageNode->getAttribute('type'));
      $ClassLibraryRegistration->libFullPath = ABLE_POLECAT_CORE;
      $ClassLibraryRegistration->useLib = '1';
      $ClassLibraryRegistration->save($Database);
      self::$Registry->addRegistration($ClassLibraryRegistration);
    }
    else {
      $message = 'core class library configuration file must contain a package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    //
    // Load master project configuration file.
    //
    $masterProjectConfFile = AblePolecat_Mode_Config::getMasterProjectConfFile();
    
    //
    // Get package (class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'package');
    $applicationNode = $Nodes->item(0);
    if (isset($applicationNode)) {
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::create();
      $ClassLibraryRegistration->id = $applicationNode->getAttribute('id');
      $ClassLibraryRegistration->name = $applicationNode->getAttribute('name');
      $ClassLibraryRegistration->libType = strtolower($applicationNode->getAttribute('type'));
      $ClassLibraryRegistration->libFullPath = AblePolecat_Server_Paths::getFullPath('src');
      $ClassLibraryRegistration->useLib = '1';
      $ClassLibraryRegistration->save($Database);
      self::$Registry->addRegistration($ClassLibraryRegistration);
    }
    else {
      //
      // @todo: this type of schema checking should be done by implementing an XML schema.
      //
      $message = 'project.xml must contain an package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }

    $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'classLibrary');
    foreach($Nodes as $key => $Node) {
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::import($Node);
      if (isset($ClassLibraryRegistration)) {
        $ClassLibraryRegistration->save($Database);
        self::$Registry->addRegistration($ClassLibraryRegistration);
      }
      
      //
      // If the class library is a module, load the corresponding project 
      // configuration file and register any dependent class libraries.
      //
      $modConfFile = AblePolecat_Mode_Config::getModuleConfFile($ClassLibraryRegistration);
      if (isset($modConfFile)) {
        $modNodes = AblePolecat_Dom::getElementsByTagName($modConfFile, 'classLibrary');
        foreach($modNodes as $key => $modNode) {
          $modClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::import($modNode);
          if ($ClassLibraryRegistration->id === $modClassLibraryRegistration->id) {
            AblePolecat_Command_Chain::triggerError(sprintf("Error in project conf file for %s. Module cannot declare itself as a dependent class library.",
              $ClassLibraryRegistration->name
            ));
          }
          if (isset($modClassLibraryRegistration)) {
            $modClassLibraryRegistration->save($Database);
            self::$Registry->addRegistration($modClassLibraryRegistration);
          }
        }
      }
    }
  }
  
  /**
   * Update current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if update fails.
   */
  public static function update(AblePolecat_DatabaseInterface $Database) {
    
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
    /**
   * Extends constructor.
   */
  protected function initialize() {
    parent::initialize();
  }
}