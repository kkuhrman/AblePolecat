<?php
/**
 * @file      polecat/core/Registry/ClassLibrary.php
 * @brief     Manages registry of third-pary class libraries used by modules.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
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
      //
      // Create instance of singleton.
      //
      self::$Registry = new AblePolecat_Registry_ClassLibrary($Subject);
        
      //
      // Load [lib]
      //
      $sql = __SQL()->
        select(
          'id', 
          'name', 
          'libType', 
          'classPrefix',
          'libFullPath', 
          'useLib', 
          'lastModifiedTime')->
        from('lib')->
        where('`useLib` = 1');
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success()) {
        $Result = $CommandResult->value();        
        foreach($Result as $key => $Library) {
          $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create($Library);
          self::$Registry->addRegistration($RegistryEntry);
        }
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Class library registry initialized.');
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
      if ($corePackageNode->hasChildNodes()) {
        foreach($corePackageNode->childNodes as $key => $childNode) {
          switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:classPrefix':
            $RegistryEntry->classPrefix = $childNode->nodeValue;
            break;
          }
        }
      }
      $RegistryEntry->classPrefix = 'AblePolecat';
      $RegistryEntry->libFullPath = ABLE_POLECAT_CORE;
      $RegistryEntry->useLib = '1';
      self::$Registry->addRegistration($RegistryEntry);
      $RegistryEntry->save($Database);
    }
    else {
      $message = 'core class library configuration file must contain a package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    //
    // Preferred class library registration is in local project configuration
    // file. This allows developers to define non-standard paths etc.
    //
    $localProjectConfFile = AblePolecat_Mode_Config::getLocalProjectConfFile();
    
    //
    // Get package (class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'package');
    $applicationNode = $Nodes->item(0);
    if (isset($applicationNode)) {
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create();
      $RegistryEntry->id = $applicationNode->getAttribute('id');
      $RegistryEntry->name = $applicationNode->getAttribute('name');
      $RegistryEntry->libType = strtolower($applicationNode->getAttribute('type'));
      if ($applicationNode->hasChildNodes()) {
        foreach($applicationNode->childNodes as $key => $childNode) {
          switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:classPrefix':
            $RegistryEntry->classPrefix = $childNode->nodeValue;
            break;
          }
        }
      }
      $RegistryEntry->libFullPath = AblePolecat_Server_Paths::getFullPath('src');
      $RegistryEntry->useLib = '1';
      self::$Registry->addRegistration($RegistryEntry);
      $RegistryEntry->save($Database);
    }
    else {
      //
      // @todo: this type of schema checking should be done by implementing an XML schema.
      //
      $message = 'project.xml must contain an package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'classLibrary');
    foreach($Nodes as $key => $Node) {
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::import($Node);
      if (isset($RegistryEntry)) {
        self::$Registry->addRegistration($RegistryEntry);
        $RegistryEntry->save($Database);
        
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
              self::$Registry->addRegistration($modRegistryEntry);
              $modRegistryEntry->save($Database);
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
    // Initialize update procedure.
    //
    $Registry = AblePolecat_Registry_ClassLibrary::wakeup();
    $Registry->beginUpdate();
    
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
      if ($corePackageNode->hasChildNodes()) {
        foreach($corePackageNode->childNodes as $key => $childNode) {
          switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:classPrefix':
            $RegistryEntry->classPrefix = $childNode->nodeValue;
            break;
          }
        }
      }
      $RegistryEntry->libFullPath = ABLE_POLECAT_CORE;
      $RegistryEntry->useLib = '1';
      if ($RegistryEntry->save($Database)) {
        $Registry->markUpdated($RegistryEntry->id, TRUE);
      }
    }
    else {
      $message = 'core class library configuration file must contain a package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    //
    // Preferred class library registration is in local project configuration
    // file. This allows developers to define non-standard paths.
    //
    $localProjectConfFile = AblePolecat_Mode_Config::getLocalProjectConfFile();
    
    //
    // Get package (class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'package');
    $applicationNode = $Nodes->item(0);
    if (isset($applicationNode)) {
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create();
      $RegistryEntry->id = $applicationNode->getAttribute('id');
      $RegistryEntry->name = $applicationNode->getAttribute('name');
      $RegistryEntry->libType = strtolower($applicationNode->getAttribute('type'));
      if ($applicationNode->hasChildNodes()) {
        foreach($applicationNode->childNodes as $key => $childNode) {
          switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:classPrefix':
            $RegistryEntry->classPrefix = $childNode->nodeValue;
            break;
          }
        }
      }
      $RegistryEntry->libFullPath = AblePolecat_Server_Paths::getFullPath('src');
      $RegistryEntry->useLib = '1';
      if ($RegistryEntry->save($Database)) {
        $Registry->markUpdated($RegistryEntry->id, TRUE);
      }
    }
    else {
      //
      // @todo: this type of schema checking should be done by implementing an XML schema.
      //
      $message = 'project.xml must contain an package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'classLibrary');
    foreach($Nodes as $key => $Node) {
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::import($Node);
      if (isset($RegistryEntry)) {
        if ($RegistryEntry->save($Database)) {
          $Registry->markUpdated($RegistryEntry->id, TRUE);
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
              if ($modRegistryEntry->save($Database)) {
                $Registry->markUpdated($modRegistryEntry->id, TRUE);
              }
            }
          }
        }
      }
    }
    
    //
    // Complete update and clean up obsolete entries.
    //
    $Registry->completeUpdate();
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
      $libFullPath = $RegistryEntry->getClassLibraryFullPath();
      if (isset($libFullPath) && AblePolecat_Server_Paths::verifyDirectory($libFullPath)) {
        //
        // Add to base registry class.
        //
        parent::addRegistration($RegistryEntry);
        
        //
        // Append class library path path to PHP INI paths
        //
        set_include_path(get_include_path() . PATH_SEPARATOR . $libFullPath);
        
        //
        // Add path to Able Polecat configurable paths.
        //
        AblePolecat_Server_Paths::setFullPath($RegistryEntry->getId(), $libFullPath);
        
        //
        // If the library is an Able Polecat module, include the local or master
        // path configuration file (if either exist in that order).
        //
        $libType = $RegistryEntry->getClassLibraryType();
        if ($libType === 'mod') {
          //
          // Able Polecat module requirements define class library full path as
          // ./project-root/usr/src
          // First check for local path configuration file.
          //
          $confPath = implode(DIRECTORY_SEPARATOR, array(
            dirname($libFullPath),
            'etc',
            'polecat',
            'conf',
            'path.config'
          ));
          if (AblePolecat_Server_Paths::verifyFile($confPath)) {
            include_once($confPath);
          }
          else {
            //
            // Local file not found, use master if it exists.
            // NOTE: path.config is not required for modules.
            //
            $confPath = implode(DIRECTORY_SEPARATOR, array(
              dirname(dirname($libFullPath)),
              'etc',
              'polecat',
              'conf',
              'path.config'
            ));
            if (AblePolecat_Server_Paths::verifyFile($confPath)) {
              include_once($confPath);
            }
          }
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
  
  /**
   * Finalize update procedure and reset update lists.
   *
   * @throw AblePolecat_Registry_Exception.
   */
  public function completeUpdate() {
    //
    // Get list of ids not effected by update.
    //
    $notUpdatedIds = $this->getUpdateList(FALSE);
    
    //
    // Remove any registered resources not in local project conf file.
    //
    if (count($notUpdatedIds)) {
      $sql = __SQL()->
        delete()->
        from('lib')->
        where(sprintf("`id` IN ('%s')", implode("','", $notUpdatedIds)));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
    }
    return parent::completeUpdate();
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