<?php
/**
 * @file      polecat/core/Registry/Resource.php
 * @brief     Manages registry application (web) resources.
 *
 * Able Polecat expects the part of the URI, which follows the host or virtual host
 * name to define a 'resource' on the system. This function returns the data (model)
 * corresponding to request. If no corresponding resource is located on the system, 
 * or if an application error is encountered along the way, Able Polecat has a few 
 * built-in resources to deal with these situations.
 *
 * NOTE: Although a 'resource' may comprise more than one path component (e.g. 
 * ./books/[ISBN] or ./products/[SKU] etc), an Able Polecat resource is identified by
 * the first part only (e.g. 'books' or 'products') combined with a UUID. Additional
 * path parts are passed to the top-level resource for further resolution. This is 
 * why resource classes validate the URI, to ensure it follows expectations for syntax
 * and that request for resource can be fulfilled. In short, the Able Polecat server
 * really only fulfils the first part of the resource request and delegates the rest to
 * the 'resource' itself.
 *
 * An Able Polecat resource maps AblePolecat_ResourceInterface to HTTP request 
 * resource (URI) on the host machine. Polecat will generate UUID for each 
 * resource in each group. Some registry objects - notably transactionClass and 
 * responseClass ([connector] and [response], respectively, in the polecat 
 * database) - reference the id of a resource. Since resource ids are generated 
 * at install time, they are referenced in the configuration file by resourceGroup 
 * attribute 'id', which then corresponds to the id attribute encapsulating the 
 * resources within a resourceClass element in the configuration file. In the 
 * configuration file, the URI path is used as the id attribute of the resource 
 * within the resourceClass element; the URI path may be assigned to the name 
 * attribute within the resourceGroup elements, but not the id as this would 
 * render the XML invalid.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Resource.php')));

class AblePolecat_Registry_Resource extends AblePolecat_RegistryAbstract {
  
  /**
   * AblePolecat_AccessControl_Article_StaticInterface
   */
  const UUID = '98d3068a-b7b2-11e4-a12d-0050569e00a2';
  const NAME = __CLASS__;
  
  /**
   * @var AblePolecat_Registry_Resource Singleton instance.
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
   * @return AblePolecat_Registry_Resource Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Registry)) {
      self::$Registry = new AblePolecat_Registry_Resource($Subject);
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
    // Get list of package resources.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'resourceClass');
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
    $Registry = AblePolecat_Registry_Resource::wakeup();
    $Registry->beginUpdate();
        
    //
    // Read registrations from local project configuration file.
    //
    $localProjectConfFile = AblePolecat_Mode_Config::getLocalProjectConfFile();
    $Nodes = AblePolecat_Dom::getElementsByTagName($localProjectConfFile, 'resourceClass');
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
    
    if (is_a($RegistryEntry, 'AblePolecat_Registry_Entry_ResourceInterface')) {      
      //
      // Add to base registry class.
      //
      parent::addRegistration($RegistryEntry);
    }
    else {
      throw new AblePolecat_Registry_Exception(sprintf("Cannot add registration to %s. %s does not implement %s.",
        __CLASS__,
        AblePolecat_Data::getDataTypeName($RegistryEntry),
        'AblePolecat_Registry_Entry_ResourceInterface'
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
      $RegistryEntry = AblePolecat_Registry_Entry_Resource::fetch($id);
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
          'hostName', 
          'classId', 
          'lastModifiedTime')->
        from('resource')->
        where(sprintf("`name` = '%s'", $name));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $Records = $CommandResult->value();
        if (isset($Records[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_Resource::create($Records[0]);
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
      // Load [resource]
      //
      $sql = __SQL()->
          select(
            'id', 
            'name', 
            'hostName', 
            'classId', 
            'lastModifiedTime')->
          from('resource');
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
      if ($CommandResult->success()) {
        $Result = $CommandResult->value();        
        foreach($Result as $key => $Record) {
          $RegistryEntry = AblePolecat_Registry_Entry_Resource::create($Record);
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
        from('resource')->
        where(sprintf("`id` IN ('%s')", implode("','", $notUpdatedIds)));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
    }
    return parent::completeUpdate();
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Return registration entry for core resource corresponding to HTTP request.
   *
   * @param AblePolecat_Message_RequestInterface $Request
   * 
   * @return AblePolecat_Registry_Entry_Resource
   */
  public static function getCoreResourceRegistration(AblePolecat_Message_RequestInterface $Request) {
    
    $ResourceRegistration = AblePolecat_Registry_Entry_Resource::create();
    $resourceName  = NULL;
    
    //
    // Resource is not registered. Use a system resource.      
    // Assign resource id and class name.
    // Extract the part of the URI, which defines the resource.
    //
    $requestPathInfo = $Request->getRequestPathInfo();
    if (isset($requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME])) {
      $resourceName = $requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME];
    }
    switch ($resourceName) {
      default:
        //
        // Request did not resolve to a registered or system resource class.
        // Log status and return error resource.
        //
        $message = sprintf("Request did not resolve to a registered resource (resource=%s; path=%s; host=%s).",
          $resourceName, 
          $Request->getRequestPath(),
          $Request->getHostName()
        );
        AblePolecat_Command_Log::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), $message, AblePolecat_LogInterface::STATUS);
        $ResourceRegistration->id = AblePolecat_Resource_Core_Error::UUID;
        $ResourceRegistration->classId = AblePolecat_Resource_Core_Error::UUID;
        break;
      case AblePolecat_Message_RequestInterface::RESOURCE_NAME_ACK:
      case AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME:
        $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME;
        $ResourceRegistration->id = AblePolecat_Resource_Core_Ack::UUID;
        $ResourceRegistration->classId = AblePolecat_Resource_Core_Ack::UUID;
        break;
      case AblePolecat_Message_RequestInterface::RESOURCE_NAME_UTIL:
        $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_UTIL;
        $ResourceRegistration->id = AblePolecat_Resource_Restricted_Util::UUID;
        $ResourceRegistration->classId = AblePolecat_Resource_Restricted_Util::UUID;
        break;
      case AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL:
        $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL;
        $ResourceRegistration->id = AblePolecat_Resource_Restricted_Install::UUID;
        $ResourceRegistration->classId = AblePolecat_Resource_Restricted_Install::UUID;
        break;
      case AblePolecat_Message_RequestInterface::RESOURCE_NAME_TEST:
        $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_TEST;
        $ResourceRegistration->id = AblePolecat_Resource_Core_Test::UUID;
        $ResourceRegistration->classId = AblePolecat_Resource_Core_Test::UUID;
        break;
      case AblePolecat_Message_RequestInterface::RESOURCE_NAME_UPDATE:
        $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_UPDATE;
        $ResourceRegistration->id = AblePolecat_Resource_Restricted_Update::UUID;
        $ResourceRegistration->classId = AblePolecat_Resource_Restricted_Update::UUID;
        break;
    }
    return $ResourceRegistration;
  }
    
  /**
   * Return registration data on resource corresponding to request URI/path.
   *
   * @see AblePolecat_ResourceAbstract::validateRequestPath()
   *
   * @param AblePolecat_Message_RequestInterface $Request
   * 
   * @return AblePolecat_Registry_Entry_Resource
   */
  public static function getRegisteredResource(AblePolecat_Message_RequestInterface $Request) {
    
    $ResourceRegistration = NULL;
    $resourceName  = NULL;
    
    //
    // Look up resource registration in [resource]
    // Extract the part of the URI, which defines the resource.
    //
    $requestPathInfo = $Request->getRequestPathInfo();
    if (isset($requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME])) {
      $resourceName = $requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME];
    }
    $sql = __SQL()->          
      select(
        'id', 
        'name', 
        'hostName', 
        'classId', 
        'lastModifiedTime')->
      from('resource')->
      where(sprintf("`name` = '%s' AND `hostName` = '%s'", $resourceName, $Request->getHostName()));
    $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
    if ($CommandResult->success()) {
      $QueryResult = $CommandResult->value();
      if (isset($QueryResult[0])) {
        $ResourceRegistration = AblePolecat_Registry_Entry_Resource::create();
        isset($QueryResult[0]['id']) ? $ResourceRegistration->id = $QueryResult[0]['id'] : NULL;
        isset($QueryResult[0]['name']) ? $ResourceRegistration->name = $QueryResult[0]['name'] : NULL;
        isset($QueryResult[0]['hostName']) ? $ResourceRegistration->hostName = $QueryResult[0]['hostName'] : NULL;
        isset($QueryResult[0]['classId']) ? $ResourceRegistration->classId = $QueryResult[0]['classId'] : NULL;
        isset($QueryResult[0]['lastModifiedTime']) ? $ResourceRegistration->lastModifiedTime = $QueryResult[0]['lastModifiedTime'] : NULL;
        
        //
        // Update cache entry if resource class file has been modified since last resource registry entry update.
        //
        if (isset($ResourceRegistration->classId)) {
          $ClassRegistration = AblePolecat_Registry_Entry_Class::fetch($ResourceRegistration->classId);
          if ($ClassRegistration && isset($ClassRegistration->lastModifiedTime)) {
            if ($ClassRegistration->lastModifiedTime > $ResourceRegistration->lastModifiedTime) {
              $sql = __SQL()->          
                update('resource')->
                set('lastModifiedTime')->
                values($ClassRegistration->lastModifiedTime)->
                where(sprintf("id = '%s'", $ResourceRegistration->id));
              $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
              $ResourceRegistration->lastModifiedTime = $ClassRegistration->lastModifiedTime;
            }
          }
        }
      }
    }
    if (!isset($ResourceRegistration)) {
      $ResourceRegistration = self::getCoreResourceRegistration($Request);
    }
    return $ResourceRegistration;
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
   * @return Array[AblePolecat_Registry_Entry_Resource].
   */
  protected static function insertNode(
    AblePolecat_DatabaseInterface $Database, 
    DOMNode $Node) {
    
    $ResourceRegistrations = array();
    
    if (!isset(self::$Registry)) {
      $message = __METHOD__ . ' Cannot call method before registry class is initialized.';
      AblePolecat_Command_Chain::triggerError($message);
    }

    $registerFlag = $Node->getAttribute('register');
    if ($registerFlag != '0') {
      $ResourceRegistrations = AblePolecat_Registry_Entry_Resource::import($Node);
      foreach($ResourceRegistrations as $key => $RegistryEntry) {
        self::$Registry->addRegistration($RegistryEntry);
        if($RegistryEntry->save($Database)) {
          self::$Registry->markUpdated($RegistryEntry->id, TRUE);
        }
      }
    }
    return $ResourceRegistrations;
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    parent::initialize();
  }
}