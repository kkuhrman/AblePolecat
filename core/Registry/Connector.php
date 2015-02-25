<?php
/**
 * @file      polecat/core/Registry/Connector.php
 * @brief     Manages registry of transaction classes.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Connector.php')));

class AblePolecat_Registry_Connector extends AblePolecat_RegistryAbstract {
  
  /**
   * AblePolecat_AccessControl_Article_StaticInterface
   */
  const UUID = 'd17c3989-b7b0-11e4-a12d-0050569e00a2';
  const NAME = __CLASS__;
  
  /**
   * @var AblePolecat_Registry_Connector Singleton instance.
   */
  private static $Registry = NULL;
  
  /**
   * @var Array Core resource connector registry entries.
   */
  private static $coreResourceRegistryEntries = NULL;
  
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
   * @return AblePolecat_Registry_Connector Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Registry)) {
      self::$Registry = new AblePolecat_Registry_Connector($Subject);
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
    // Load master project configuration file.
    //
    $masterProjectConfFile = AblePolecat_Mode_Config::getMasterProjectConfFile();
    
    //
    // Get list of package connectors.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'connector');
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
    
    if (is_a($RegistryEntry, 'AblePolecat_Registry_Entry_ConnectorInterface')) {      
      //
      // Add to base registry class.
      //
      parent::addRegistration($RegistryEntry);
    }
    else {
      throw new AblePolecat_Registry_Exception(sprintf("Cannot add registration to %s. %s does not implement %s.",
        __CLASS__,
        AblePolecat_Data::getDataTypeName($RegistryEntry),
        'AblePolecat_Registry_Entry_ConnectorInterface'
      ));
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Return connector registration entry for core resource by given id.
   *
   * @param UUID $connectorId
   * @param string $requestMethod
   *
   * @return AblePolecat_Registry_Entry_ConnectorInterface or NULL.
   */
  public static function getCoreResourceConnector($resourceId, $requestMethod) {
    
    $ConnectorRegistration = NULL;
    
    if (!isset(self::$coreResourceRegistryEntries)) {
      self::$coreResourceRegistryEntries = array(
        AblePolecat_Resource_Core_Ack::UUID => array(
          'resourceId' => AblePolecat_Resource_Core_Ack::UUID,
          'classId' => AblePolecat_Transaction_Get_Resource::UUID,
          'accessDeniedCode' => 200
        ),
        AblePolecat_Resource_Restricted_Install::UUID => array(
          'resourceId' => AblePolecat_Resource_Restricted_Install::UUID, 
          'classId' => AblePolecat_Transaction_Restricted_Install::UUID,
          'authorityClassName' => 'AblePolecat_Transaction_AccessControl_Authority',
          'accessDeniedCode' => 401,
        ),
        AblePolecat_Resource_Restricted_Update::UUID => array(
          'resourceId' => AblePolecat_Resource_Restricted_Update::UUID,
          'classId' => AblePolecat_Transaction_Restricted_Update::UUID,
          'authorityClassName' => 'AblePolecat_Transaction_AccessControl_Authority',
          'accessDeniedCode' => 401,
        ),
        AblePolecat_Resource_Restricted_Util::UUID => array(
          'resourceId' => AblePolecat_Resource_Restricted_Util::UUID,
          'classId' => AblePolecat_Transaction_Restricted_Util::UUID,
          'authorityClassName' => 'AblePolecat_Transaction_AccessControl_Authority',
          'accessDeniedCode' => 401,
        ),
      );
    }
    
    if (!isset(self::$coreResourceRegistryEntries[$resourceId])) {
      //
      // @todo: The default is 'resource not found' (404).
      //  
    }
    else {
      $connectorData = NULL;
      switch ($requestMethod) {
        default:
          break;
        case 'GET':
          $connectorData = self::$coreResourceRegistryEntries[$resourceId];
          break;
        case 'POST':
          //
          // All forms are processed through index.php
          //
          $Request = AblePolecat_Host::getRequest();
          $redirect = $Request->getQueryStringFieldValue(AblePolecat_Transaction_RestrictedInterface::ARG_REDIRECT);
          $referer = $Request->getQueryStringFieldValue(AblePolecat_Transaction_RestrictedInterface::ARG_REFERER);
          if (isset($redirect) && ($redirect != '')) {
            //
            // This would be the case when submission of a form redirects to a 
            // resource other than the one, which presented the form.
            //
            if (isset(self::$coreResourceRegistryEntries[$redirect])) {
              $connectorData = self::$coreResourceRegistryEntries[$redirect];
            }
          }
          else {
            if (isset($referer) && ($referer != '')) {
              //
              // The referer presented the form and intends to POST to itself.
              //
              if (isset(self::$coreResourceRegistryEntries[$referer])) {
                $connectorData = self::$coreResourceRegistryEntries[$referer];
              }
            }
          }
          break;
      }
      if (isset($connectorData)) {
        $ConnectorRegistration = AblePolecat_Registry_Entry_Connector::create();
        isset($connectorData['classId']) ? $ConnectorRegistration->classId = $connectorData['classId'] : $ConnectorRegistration->classId = NULL;
        isset($connectorData['authorityClassName']) ? $ConnectorRegistration->authorityClassName = $connectorData['authorityClassName'] : $ConnectorRegistration->authorityClassName = NULL;
        isset($connectorData['accessDeniedCode']) ? $ConnectorRegistration->accessDeniedCode = $connectorData['accessDeniedCode'] : $ConnectorRegistration->accessDeniedCode = NULL;
      }
      else {
        //
        // @todo: this is a 405 (Method Not Allowed) condition.
        //
        $message = sprintf("%s request for ./%s - Method Not Allowed (405).", $requestMethod, $resourceId);
        AblePolecat_Command_Chain::triggerError($message);
      }
    }
    return $ConnectorRegistration;
  }
  
  /**
   * Return registration data on connector corresponding to request path and method.
   *
   * @param UUID $resourceId
   * @param string $requestMethod
   *
   * @return AblePolecat_Registry_Entry_ConnectorInterface or NULL.
   */
  public static function getRegisteredResourceConnector($resourceId, $requestMethod) {
    
    $ConnectorRegistration = NULL;
    
    if (AblePolecat_Database_Pdo::ready()) {
      //
      // Get project database.
      //
      $CoreDatabase = AblePolecat_Database_Pdo::wakeup();
      
      //
      // Load [lib]
      //
      $sql = __SQL()->
      select(
        'id', 
        'name', 
        'resourceId', 
        'requestMethod',
        'accessDeniedCode', 
        'classId', 
        'lastModifiedTime')->
      from('connector')->
      where(sprintf("`resourceId` = '%s' AND `requestMethod` = '%s'", $resourceId, $requestMethod));
      $QueryResult = $CoreDatabase->query($sql);
      if (isset($QueryResult[0])) {
        $ConnectorRegistration = new AblePolecat_Registry_Entry_Connector();
        isset($QueryResult[0]['id']) ? $ConnectorRegistration->id = $QueryResult[0]['id'] : NULL;
        isset($QueryResult[0]['name']) ? $ConnectorRegistration->name = $QueryResult[0]['name'] : NULL;
        isset($QueryResult[0]['resourceId']) ? $ConnectorRegistration->resourceId = $QueryResult[0]['resourceId'] : NULL;
        isset($QueryResult[0]['requestMethod']) ? $ConnectorRegistration->requestMethod = $QueryResult[0]['requestMethod'] : NULL;
        isset($QueryResult[0]['accessDeniedCode']) ? $ConnectorRegistration->accessDeniedCode = $QueryResult[0]['accessDeniedCode'] : NULL;
        isset($QueryResult[0]['classId']) ? $ConnectorRegistration->classId = $QueryResult[0]['classId'] : NULL;
        isset($QueryResult[0]['lastModifiedTime']) ? $ConnectorRegistration->lastModifiedTime = $QueryResult[0]['lastModifiedTime'] : NULL;
        if (isset(self::$Registry)) {
          self::$Registry->addRegistration($ConnectorRegistration);
        }
      }
    }
    if (!isset($ConnectorRegistration)) {
      //
      // Handle built-in resources in the event database connection is not active.
      //
      $ConnectorRegistration = self::getCoreResourceConnector($resourceId, $requestMethod);
    }
    return $ConnectorRegistration;
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
      $ConnectorRegistration = AblePolecat_Registry_Entry_Connector::import($Node);
      $ConnectorRegistration->save($Database);
      self::$Registry->addRegistration($ConnectorRegistration);
    }
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    parent::initialize();
  }
}