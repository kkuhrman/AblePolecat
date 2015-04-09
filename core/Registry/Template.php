<?php
/**
 * @file      polecat/core/Registry/Template.php
 * @brief     Manages registry of HTTP response document templates.
 *
 * An Able Polecat template provides content and representation instructions 
 * for one or more components or responses. In the database, the field articleId 
 * references a response id or a component id. In the configuration file, this 
 * field references the respective response or component class id.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Template.php')));

class AblePolecat_Registry_Template extends AblePolecat_RegistryAbstract {
  
  /**
   * AblePolecat_AccessControl_Article_StaticInterface
   */
  const UUID = 'e0cb0cc9-b7b2-11e4-a12d-0050569e00a2';
  const NAME = __CLASS__;
  
  /**
   * @var AblePolecat_Registry_Template Singleton instance.
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
   * @return AblePolecat_Registry_Template Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Registry)) {
      self::$Registry = new AblePolecat_Registry_Template($Subject);
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
    // Get list of package templates.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'template');
    self::insertList($Database, $Nodes);
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
    $Registry = AblePolecat_Registry_Template::wakeup();
    $Registry->beginUpdate();
    
    //
    // Load local project configuration file.
    //
    $localProjectConfFile = AblePolecat_Mode_Config::getLocalProjectConfFile();
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'template');
    foreach($Nodes as $key => $Node) {
      self::insertNode($Database, $Node);
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
    
    if (is_a($RegistryEntry, 'AblePolecat_Registry_Entry_TemplateInterface')) {      
      //
      // Add to base registry class.
      //
      parent::addRegistration($RegistryEntry);
    }
    else {
      throw new AblePolecat_Registry_Exception(sprintf("Cannot add registration to %s. %s does not implement %s.",
        __CLASS__,
        AblePolecat_Data::getDataTypeName($RegistryEntry),
        'AblePolecat_Registry_Entry_TemplateInterface'
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
      $RegistryEntry = AblePolecat_Registry_Entry_Template::fetch($id);
      if (!isset($RegistryEntry)) {
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
        select(
          'id',
          'name',
          'themeName', 
          'templateScope', 
          'articleId', 
          'docType', 
          'fullPath', 
          'lastModifiedTime')->
        from('template')->
        where(sprintf("`name` = '%s'", $name));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $Records = $CommandResult->value();
        if (isset($Records[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_Template::create($Records[0]);
        }
      }
      if (!isset($RegistryEntry)) {
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
      // Load [template]
      //
      $sql = __SQL()->
          select(
            'id',
            'name',
            'themeName', 
            'templateScope', 
            'articleId', 
            'docType', 
            'fullPath', 
            'lastModifiedTime')->
          from('template');
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success()) {
        $Result = $CommandResult->value();        
        foreach($Result as $key => $Record) {
          $RegistryEntry = AblePolecat_Registry_Entry_Template::create($Record);
          self::$Registry->addRegistration($RegistryEntry);
        }
      }
    }
    $Registrations = parent::getRegistrations($registryKey, $value);
    return $Registrations;
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
        from('template')->
        where(sprintf("`id` IN ('%s')", implode("','", $notUpdatedIds)));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
    }
    return parent::completeUpdate();
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Fetch registration record given by article id.
   *
   * @param string $articleId.
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public static function getRegistrationsByArticleId($articleId) {
    
    $Registrations = array();
    
    //
    // Generate and execute SELECT statement.
    //
    $sql = __SQL()->          
      select(
        'id',
        'name',
        'themeName', 
        'templateScope', 
        'articleId', 
        'docType', 
        'fullPath', 
        'lastModifiedTime')->
      from('template')->
      where(sprintf("`articleId` = '%s'", $articleId));
    $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
    if ($CommandResult->success() && is_array($CommandResult->value())) {
      $registrationInfo = $CommandResult->value();
      if (is_array($registrationInfo)) {
        foreach($registrationInfo as $key => $Record) {
          $RegistryEntry = AblePolecat_Registry_Entry_Template::create($Record);
          $Registrations[] = $RegistryEntry;
        }
      }
    }
    return $Registrations;
  }
  
  /**
   * Insert DOMNodeList into registry.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   * @param DOMNodeList $Nodes List of DOMNodes containing registry entries.
   *
   */
  protected static function insertList(
    AblePolecat_DatabaseInterface $Database, 
    DOMNodeList $Nodes) {
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
  protected static function insertNode(
    AblePolecat_DatabaseInterface $Database, 
    DOMNode $Node) {

    if (!isset(self::$Registry)) {
      $message = __METHOD__ . ' Cannot call method before registry class is initialized.';
      AblePolecat_Command_Chain::triggerError($message);
    }

    $registerFlag = $Node->getAttribute('register');
    if ($registerFlag != '0') {
      $TemplateRegistrations = AblePolecat_Registry_Entry_Template::import($Node);
      foreach($TemplateRegistrations as $key => $TemplateRegistration) {
        self::$Registry->addRegistration($TemplateRegistration);
        if ($TemplateRegistration->save($Database)) {
          self::$Registry->markUpdated($TemplateRegistration->id, TRUE);
        }
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