<?php
/**
 * @file      polecat/core/Registry/Component.php
 * @brief     Manages registry of components.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode', 'Component.php')));

class AblePolecat_Registry_Component extends AblePolecat_RegistryAbstract {
  
  /**
   * AblePolecat_AccessControl_Article_StaticInterface
   */
  const UUID = 'c4c317f9-b7b1-11e4-a12d-0050569e00a2';
  const NAME = __CLASS__;
  
  /**
   * @var AblePolecat_Registry_Component Singleton instance.
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
   * @return AblePolecat_Registry_Component Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Registry)) {
      self::$Registry = new AblePolecat_Registry_Component($Subject);
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
    // Load local project configuration file.
    //
    $localProjectConfFile = AblePolecat_Mode_Config::getLocalProjectConfFile();
    
    //
    // Get package (class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'package');
    $applicationNode = $Nodes->item(0);
    if (!isset($applicationNode)) {
      //
      // @todo: this type of schema checking should be done by implementing an XML schema.
      //
      $message = 'project.xml must contain an package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'component');
    self::insertList($Database, $Nodes);
    
    //
    // Import components in class libraries.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'classLibrary');
    foreach($Nodes as $key => $Node) {
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::import($Node);
      if (isset($ClassLibraryRegistration)) {
        $modConfFile = AblePolecat_Mode_Config::getModuleConfFile($ClassLibraryRegistration);
        if (isset($modConfFile)) {
          $modNodes = AblePolecat_Dom::getElementsByTagName($modConfFile, 'component');
          self::insertList($Database, $modNodes);
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
    $Registry = AblePolecat_Registry_Component::wakeup();
    $CurrentRegistrations = $Registry->getRegistrations(self::KEY_ARTICLE_ID);
    
    //
    // Make a list of potential delete candidates.
    //
    $CurrentRegistrationIds = array_flip(array_keys($CurrentRegistrations));
    
    //
    // Load local project configuration file.
    //
    $localProjectConfFile = AblePolecat_Mode_Config::getLocalProjectConfFile();
    
    //
    // Get package (class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'package');
    $applicationNode = $Nodes->item(0);
    if (!isset($applicationNode)) {
      //
      // @todo: this type of schema checking should be done by implementing an XML schema.
      //
      $message = 'project.xml must contain an package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'component');
    foreach($Nodes as $key => $Node) {
      self::insertNode($Database, $Node);
      
      //
      // Since entry is in master project conf file, remove it from delete list.
      //
      $id = $Node->getAttribute('id');
      if (isset($CurrentRegistrationIds[$id])) {
        unset($CurrentRegistrationIds[$id]);
      }
    }
    
    //
    // Import components in class libraries.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'classLibrary');
    foreach($Nodes as $key => $Node) {
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::import($Node);
      if (isset($ClassLibraryRegistration)) {
        $modConfFile = AblePolecat_Mode_Config::getModuleConfFile($ClassLibraryRegistration);
        if (isset($modConfFile)) {
          $modNodes = AblePolecat_Dom::getElementsByTagName($modConfFile, 'component');
          foreach($modNodes as $key => $Node) {
            self::insertNode($Database, $Node);
            
            //
            // Since entry is in master project conf file, remove it from delete list.
            //
            $id = $Node->getAttribute('id');
            if (isset($CurrentRegistrationIds[$id])) {
              unset($CurrentRegistrationIds[$id]);
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
        from('component')->
        where(sprintf("`id` IN ('%s')", implode("','", array_flip($CurrentRegistrationIds))));
      $Database->execute($sql);
    }
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
    
    if (is_a($RegistryEntry, 'AblePolecat_Registry_Entry_ComponentInterface')) {
      //
      // Add to base registry class.
      //
      parent::addRegistration($RegistryEntry);
    }
    else {
      throw new AblePolecat_Registry_Exception(sprintf("Cannot add registration to %s. %s does not implement %s.",
        __CLASS__,
        AblePolecat_Data::getDataTypeName($RegistryEntry),
        'AblePolecat_Registry_Entry_ComponentInterface'
      ));
    }
  }
  
  /**
   * Retrieve registered object by given id.
   *
   * @param UUID $id Id of registered object.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function getRegistrationById($id) {
    
    $RegistryEntry = parent::getRegistrationById($id);
    if (!isset($RegistryEntry)) {
      $RegistryEntry = AblePolecat_Registry_Entry_DomNode_Component::fetch($id);
      if (isset($RegistryEntry)) {
        parent::addRegistration($RegistryEntry);
      }
    }
    return $RegistryEntry;
  }
  
  /**
   * Retrieve registered object by given name.
   *
   * @param string $name Name of registered object.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function getRegistrationByName($name) {
    
    $RegistryEntry = parent::getRegistrationByName($name);
    if (!isset($RegistryEntry)) {
      $sql = __SQL()->
        select('id', 'name', 'classId')->
        from('component')->
        where(sprintf("`name` = '%s'", $name));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $Records = $CommandResult->value();
        if (isset($Records[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_DomNode_Component($Records[0]);
        }
      }
      if (isset($RegistryEntry)) {
        parent::addRegistration($RegistryEntry);
      }
    }
    return $RegistryEntry;
  }
  
  /**
   * Retrieve a list of registered objects corresponding to the given key name/value.
   *
   * Some registry classes (AblePolecat_Registry_ClassInterface, AblePolecat_Registry_ClassLibrary)
   * load all registry entries at wakeup() as any number (if not all) are in demand at run time.
   * Others will be queried for only one or a small number of entries, depending on HTTP request.
   * This function is provided in those cases where all entries must be retrieved.
   * 
   * @param string $keyName The name of a registry key.
   * @param string $keyValue Optional value of registry key.
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public function getRegistrations($registryKey, $value = NULL) {
    
    $Registrations = array();
    
    if (0 === $this->getRegistrationCount()) {
      //
      // Load [component]
      //
      $sql = __SQL()->
        select('id', 'name', 'classId')->
        from('component');
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success()) {
        $Result = $CommandResult->value();        
        foreach($Result as $key => $Record) {
          $RegistryEntry = AblePolecat_Registry_Entry_DomNode_Component::create($Record);
          self::$Registry->addRegistration($RegistryEntry);
        }
      }
    }
    $Registrations = parent::getRegistrations($registryKey, $value);
    return $Registrations;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Insert DOMNodeList into registry.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   * @param DOMNodeList $Nodes List of DOMNodes containing registry entries.
   *
   */
  protected static function insertList(AblePolecat_DatabaseInterface $Database, DOMNodeList $Nodes) {    
    foreach($Nodes as $key => $Node) {
      self::insertNode($Database, $Node);
    }
  }
  
  /**
   * Insert DOMNode into registry.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   * @param DOMNode $Node DOMNode containing registry entry.
   *
   */
  protected static function insertNode(AblePolecat_DatabaseInterface $Database, DOMNode $Node) {
    
    if (!isset(self::$Registry)) {
      $message = __METHOD__ . ' Cannot call method before registry class is initialized.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    $registerFlag = $Node->getAttribute('register');
    if ($registerFlag != '0') {
      $ComponentRegistration = AblePolecat_Registry_Entry_DomNode_Component::import($Node);
      if (isset($ComponentRegistration)) {
        self::$Registry->addRegistration($ComponentRegistration);
        $ComponentRegistration->save($Database);
      }
    }
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    parent::initialize();
  }
}