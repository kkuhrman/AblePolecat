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
   * @var AblePolecat_Registry_Connector Singleton instance.
   */
  private static $Registry = NULL;
  
  /**
   * @var Array Core resource connector registry entries.
   */
  private static $coreResourceRegistryEntries = NULL;
  
  /**
   * @var List of Able Polecat modules.
   */
  private $ClassLibraries = NULL;
  
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
      try {
        self::$Registry = new AblePolecat_Registry_Connector($Subject);
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
    
    if (!isset(self::$Registry)) {
      //
      // Create instance of singleton.
      //
      self::$Registry = new AblePolecat_Registry_Connector();

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
        $ConnectorRegistration = AblePolecat_Registry_Entry_Connector::create();
        $ConnectorRegistration->id = $applicationNode->getAttribute('id');
        $ConnectorRegistration->name = $applicationNode->getAttribute('name');
        $ConnectorRegistration->libType = strtolower($applicationNode->getAttribute('type'));
        $ConnectorRegistration->libFullPath = AblePolecat_Server_Paths::getFullPath('src');
        $ConnectorRegistration->useLib = '1';
        $ConnectorRegistration->save($Database);
      }
      else {
        //
        // @todo: this type of schema checking should be done by implementing an XML schema.
        //
        $message = 'project.xml must contain an package node.';
        trigger_error($message, E_USER_ERROR);
      }

      //
      // Create DML statements for classes.
      //
      $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'connector');
      foreach($Nodes as $key => $Node) {
        $ConnectorRegistration = AblePolecat_Registry_Entry_Connector::import($Node);
        if (isset($ConnectorRegistration)) {
          $ConnectorRegistration->save($Database);
        }
        
        //
        // If the class library is a module, load the corresponding project 
        // configuration file and register any dependent class libraries.
        //
        if ($ConnectorRegistration->libType === 'mod') {
          $modConfFilePath = implode(DIRECTORY_SEPARATOR, array(
            $ConnectorRegistration->libFullPath,
            'etc',
            'polecat',
            'conf',
            AblePolecat_Server_Paths::CONF_FILENAME_PROJECT
          ));
          $modConfFile = new DOMDocument();
          $modConfFile->load($modConfFilePath);
          
          $modNodes = AblePolecat_Dom::getElementsByTagName($modConfFile, 'connector');
          foreach($modNodes as $key => $modNode) {
            $modConnectorRegistration = AblePolecat_Registry_Entry_Connector::import($modNode);
            if (isset($modConnectorRegistration)) {
              $modConnectorRegistration->save($Database);
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
    
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Return connector registration entry for core resource by given id.
   *
   * @param UUID $resourceId
   * @param string $requestMethod
   *
   * @return AblePolecat_Registry_Entry_ConnectorInterface or NULL.
   */
  public static function getCoreResourceConnector($resourceId, $requestMethod) {
    
    $ConnectorRegistration = NULL;
    
    if (!isset(self::$coreResourceRegistryEntries)) {
      self::$coreResourceRegistryEntries = array(
        AblePolecat_Resource_Core_Ack::UUID => array(
          'accessDeniedCode' => 200
        ),
        AblePolecat_Resource_Restricted_Install::UUID => array(
          'transactionClassName' => 'AblePolecat_Transaction_Restricted_Install',
          'authorityClassName' => 'AblePolecat_Transaction_AccessControl_Authority',
          'accessDeniedCode' => 401,
        ),
        AblePolecat_Resource_Restricted_Update::UUID => array(
          'transactionClassName' => 'AblePolecat_Transaction_Restricted_Update',
          'authorityClassName' => 'AblePolecat_Transaction_AccessControl_Authority',
          'accessDeniedCode' => 401,
        ),
        AblePolecat_Resource_Restricted_Util::UUID => array(
          'transactionClassName' => 'AblePolecat_Transaction_Restricted_Util',
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
        isset($connectorData['transactionClassName']) ? $ConnectorRegistration->transactionClassName = $connectorData['transactionClassName'] : $ConnectorRegistration->transactionClassName = NULL;
        isset($connectorData['authorityClassName']) ? $ConnectorRegistration->authorityClassName = $connectorData['authorityClassName'] : $ConnectorRegistration->authorityClassName = NULL;
        isset($connectorData['accessDeniedCode']) ? $ConnectorRegistration->accessDeniedCode = $connectorData['accessDeniedCode'] : $ConnectorRegistration->accessDeniedCode = NULL;
      }
      else {
        //
        // @todo: this is a 405 (Method Not Allowed) condition.
        //
        $message = sprintf("%s request for ./%s - Method Not Allowed (405).", $requestMethod, $resourceId);
        AblePolecat_Registry_Connector::triggerError($message);
      }
    }
    return $ConnectorRegistration;
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    //
    // Supported modules.
    //
    $this->ClassLibraries = array();
  }
}