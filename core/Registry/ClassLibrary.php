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
            select(
              'id', 
              'name', 
              'libType', 
              'libFullPath', 
              'useLib', 
              'lastModifiedTime')->
            from('lib')->
            where('`useLib` = 1');
          $QueryResult = $CoreDatabase->query($sql);
          foreach($QueryResult as $key => $Library) {
            $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create($Library);
            self::$Registry->addRegistration($RegistryEntry);
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
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create();
      $RegistryEntry->id = $corePackageNode->getAttribute('id');
      $RegistryEntry->name = $corePackageNode->getAttribute('name');
      $RegistryEntry->libType = strtolower($corePackageNode->getAttribute('type'));
      $RegistryEntry->libFullPath = ABLE_POLECAT_CORE;
      $RegistryEntry->useLib = '1';
      $RegistryEntry->save($Database);
      self::$Registry->addRegistration($RegistryEntry);
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
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create();
      $RegistryEntry->id = $applicationNode->getAttribute('id');
      $RegistryEntry->name = $applicationNode->getAttribute('name');
      $RegistryEntry->libType = strtolower($applicationNode->getAttribute('type'));
      $RegistryEntry->libFullPath = AblePolecat_Server_Paths::getFullPath('src');
      $RegistryEntry->useLib = '1';
      $RegistryEntry->save($Database);
      self::$Registry->addRegistration($RegistryEntry);
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
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::import($Node);
      if (isset($RegistryEntry)) {
        $RegistryEntry->save($Database);
        self::$Registry->addRegistration($RegistryEntry);
        
        //
        // If the class library is a module, load the corresponding project 
        // configuration file and register any dependent class libraries.
        //
        $modConfFile = AblePolecat_Mode_Config::getModuleConfFile($RegistryEntry);
        if (isset($modConfFile)) {
          $modNodes = AblePolecat_Dom::getElementsByTagName($modConfFile, 'classLibrary');
          foreach($modNodes as $key => $modNode) {
            $modRegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::import($modNode);
            if ($RegistryEntry->id === $modRegistryEntry->id) {
              AblePolecat_Command_Chain::triggerError(sprintf("Error in project conf file for %s. Module cannot declare itself as a dependent class library.",
                $RegistryEntry->name
              ));
            }
            if (isset($modRegistryEntry)) {
              $modRegistryEntry->save($Database);
              self::$Registry->addRegistration($modRegistryEntry);
            }
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
    //
    // Get current registrations.
    //
    $Registry = AblePolecat_Registry_ClassLibrary::wakeup();
    $CurrentRegistrations = $Registry->getRegistrations(self::KEY_ARTICLE_ID);
    
    //
    // Make a list of potential delete candidates.
    //
    $CurrentRegistrationIds = array_flip(array_keys($CurrentRegistrations));
    
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
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create();
      $RegistryEntry->id = $corePackageNode->getAttribute('id');
      $RegistryEntry->name = $corePackageNode->getAttribute('name');
      $RegistryEntry->libType = strtolower($corePackageNode->getAttribute('type'));
      $RegistryEntry->libFullPath = ABLE_POLECAT_CORE;
      $RegistryEntry->useLib = '1';
      $RegistryEntry->save($Database);
      
      //
      // Unflag delete entry.
      //
      $id = $RegistryEntry->id;
      if (isset($CurrentRegistrationIds[$id])) {
        unset($CurrentRegistrationIds[$id]);
      }
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
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create();
      $RegistryEntry->id = $applicationNode->getAttribute('id');
      $RegistryEntry->name = $applicationNode->getAttribute('name');
      $RegistryEntry->libType = strtolower($applicationNode->getAttribute('type'));
      $RegistryEntry->libFullPath = AblePolecat_Server_Paths::getFullPath('src');
      $RegistryEntry->useLib = '1';
      $RegistryEntry->save($Database);
      
      //
      // Unflag delete entry.
      //
      $id = $RegistryEntry->id;
      if (isset($CurrentRegistrationIds[$id])) {
        unset($CurrentRegistrationIds[$id]);
      }
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
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::import($Node);
      if (isset($RegistryEntry)) {
        $RegistryEntry->save($Database);
        
        //
        // Unflag delete entry.
        //
        $id = $RegistryEntry->id;
        if (isset($CurrentRegistrationIds[$id])) {
          unset($CurrentRegistrationIds[$id]);
        }
        
        //
        // If the class library is a module, load the corresponding project 
        // configuration file and register any dependent class libraries.
        //
        $modConfFile = AblePolecat_Mode_Config::getModuleConfFile($RegistryEntry);
        if (isset($modConfFile)) {
          $modNodes = AblePolecat_Dom::getElementsByTagName($modConfFile, 'classLibrary');
          foreach($modNodes as $key => $modNode) {
            $modRegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::import($modNode);
            if ($RegistryEntry->id === $modRegistryEntry->id) {
              AblePolecat_Command_Chain::triggerError(sprintf("Error in project conf file for %s. Module cannot declare itself as a dependent class library.",
                $RegistryEntry->name
              ));
            }
            if (isset($modRegistryEntry)) {
              $modRegistryEntry->save($Database);
              
              //
              // Unflag delete entry.
              //
              $id = $modRegistryEntry->id;
              if (isset($CurrentRegistrationIds[$id])) {
                unset($CurrentRegistrationIds[$id]);
              }
            }
          }
        }
      }
    }
    
    //
    // Remove any registered classes not in master project conf file.
    //
    if (count($CurrentRegistrationIds)) {
      $sql = __SQL()->
        delete()->
        from('lib')->
        where(sprintf("`id` IN ('%s')", implode("','", array_flip($CurrentRegistrationIds))));
      $Database->execute($sql);
    }
    
    //
    // @todo: Refresh.
    //
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_RegistryInterface.
   ********************************************************************************/
  
  /**
   * Add a registry entry.
   *
   * @param AblePolecat_Registry_EntryInterface $RegistryEntry
   *
   * @throw AblePolecat_Registry_Exception If entry is incompatible.
   */
  public function addRegistration(AblePolecat_Registry_EntryInterface $RegistryEntry) {
    
    if (is_a($RegistryEntry, 'AblePolecat_Registry_Entry_ClassLibrary')) {
      if (isset($RegistryEntry->libFullPath) && AblePolecat_Server_Paths::verifyDirectory($RegistryEntry->libFullPath)) {
        //
        // Add to base registry class.
        //
        parent::addRegistration($RegistryEntry);
        
        //
        // Append class library path path to PHP INI paths
        //
        set_include_path(get_include_path() . PATH_SEPARATOR . $RegistryEntry->libFullPath);
        
        //
        // Add path to Able Polecat configurable paths.
        //
        AblePolecat_Server_Paths::setFullPath($RegistryEntry->id, $RegistryEntry->libFullPath);
        
        //
        // if there is a paths.config file, include that, too...
        //
        $pathConfFilePath = implode(DIRECTORY_SEPARATOR, 
          array(
            $RegistryEntry->libFullPath, 
            'etc', 
            'polecat', 
            'conf', 
            'path.config'
          )
        );
        if (file_exists($pathConfFilePath) && (ABLE_POLECAT_ROOT_PATH_CONF_FILE_PATH != $pathConfFilePath)) {
          include_once($pathConfFilePath);
        }
      }
      else {
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, 
          sprintf("Invalid path provided for class library %s (%s).", $RegistryEntry->id, $RegistryEntry->name)
        );
      }
      
    }
    else {
      throw new AblePolecat_Registry_Exception(sprintf("Cannot add registration to %s. %s does not implement %s.",
        __CLASS__,
        AblePolecat_Data::getDataTypeName($RegistryEntry),
        'AblePolecat_Registry_Entry_ClassLibrary'
      ));
    }
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