<?php
/**
 * @file      polecat/core/Registry/Entry/Connector.php
 * @brief     Encapsulates record of a connector registered in [connector].
 * 
 * Classes implementing AblePolecat_TransactionInterface might easily be compared
 * with action controllers in MVC parlance. A 'Connector' in Able Polecat binds a
 * resource to a specific transaction class, based on the request method (i.e. GET, 
 * POST, PUT, DELETE, etc). In this manner, Able Polecat resources expose a uniform
 * interface on the web.
 *
 * The Able Polecat connector also deals with details such as whether a URL is 
 * pointing to a specific state of a resource (representation); for example, a 
 * specific page from a paginated list of search results. In this manner, Able 
 * Polecat resources achieve statelessness (all the information necessary for 
 * server to fulfill request is in the request).
 *
 * Carrying the example above further, Able Polecat connector is responsible for 
 * providing the representation of resource with links to related resources; for 
 * example, links to the other pages in the list of search results above. In this 
 * manner, Able Polecat resources meet the connectedness property of ROA.
 *     
 * @see Richardson/Ruby, RESTful Web Services (ISBN 978-0-596-52926-0)
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Install.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Update.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Util.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Test.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Unrestricted.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ConnectorInterface extends AblePolecat_Registry_EntryInterface {
  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return string
   */
  public function getRequestMethod();
  
  /**
   * @return int.
   */
  public function getAccessDeniedCode();
  
  /**
   * @return string.
   */
  public function getClassId();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Connector extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ConnectorInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    //
    // Create instance of class.
    //
    $RegistryEntry = new AblePolecat_Registry_Entry_Connector();
    
    //
    // Check method arguments for database record.
    //
    $args = func_get_args();
    if (isset($args[0]) && is_array($args[0])) {
      $Record = $args[0];
      isset($Record['id']) ? $RegistryEntry->id = $Record['id'] : NULL;
      isset($Record['name']) ? $RegistryEntry->name = $Record['name'] : NULL;
      isset($Record['resourceId']) ? $RegistryEntry->resourceId = $Record['resourceId'] : NULL;
      isset($Record['requestMethod']) ? $RegistryEntry->requestMethod = $Record['requestMethod'] : NULL;
      isset($Record['accessDeniedCode']) ? $RegistryEntry->accessDeniedCode = $Record['accessDeniedCode'] : NULL;
      isset($Record['classId']) ? $RegistryEntry->classId = $Record['classId'] : NULL;
      isset($Record['lastModifiedTime']) ? $RegistryEntry->lastModifiedTime = $Record['lastModifiedTime'] : NULL;
    }
    return $RegistryEntry;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * Generate registry entry data from project configuration file element(s).
   *
   * @param DOMNode $Node Registry entry data from project configuration file.
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public static function import(DOMNode $Node) {
    
    $RegistryEntries = array();
    
    if (is_a($Node, 'DOMElement') && ($Node->tagName == 'polecat:transactionClass') && $Node->hasChildNodes()) {
      //
      // Verify class reference.
      //
      $classId = $Node->getAttribute('id');
      $className = $Node->getAttribute('name');
      $lastModifiedTime = 0;
      $ClassRegistration = AblePolecat_Registry_Class::wakeup()->
        getRegistrationById($classId);
      if (isset($ClassRegistration)) {
        $lastModifiedTime = $ClassRegistration->lastModifiedTime;
      }
      else {
        $message = sprintf("Transaction class %s references invalid class id %s.",
          $className,
          $classId
        );
        AblePolecat_Command_Chain::triggerError($message);
      }
      
      //
      // Check for connectors already registered.
      // Unique key is resourceId + requestMethod.
      //
      $registeredConnectors = array();
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
        where(sprintf("`classId` = '%s'", $classId));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        foreach ($registrationInfo as $key => $Record) {
          $resourceId = $Record['resourceId'];
          $requestMethod = $Record['requestMethod'];
          !isset($registeredConnectors[$resourceId]) ? $registeredConnectors[$resourceId] = array() : NULL;
          $registeredConnectors[$resourceId][$requestMethod] = AblePolecat_Registry_Entry_Connector::create($Record);
        }
      }
      
      foreach($Node->childNodes as $key => $childNode) {
        switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:resourceGroups':
            $resourceGroups = self::importResourceGroups($childNode);
            foreach($resourceGroups as $resourceGroupKey => $resourceGroup) {
              $requestMethod = '';
              $accessDeniedCode = 0;
              if(isset($resourceGroup['attributes'])) {
                $requestMethod = $resourceGroup['attributes']->getNamedItem('requestMethod')->value;
                $accessDeniedCode = intval($resourceGroup['attributes']->getNamedItem('accessDeniedCode')->value);
              }
              foreach($resourceGroup['resources'] as $resourceId => $ResourceNode) {
                //
                // Check if connector is already registered.
                //
                $RegistryEntry = NULL;
                if (isset($registeredConnectors[$resourceId][$requestMethod])) {
                  $RegistryEntry = $registeredConnectors[$resourceId][$requestMethod];
                }
                else {
                  $RegistryEntry = AblePolecat_Registry_Entry_Connector::create();
                  $RegistryEntry->id = self::generateUUID();
                  $RegistryEntry->requestMethod = $requestMethod;
                  $RegistryEntry->resourceId = $resourceId;
                }
                $resourceName = $ResourceNode->getAttribute('name');
                $RegistryEntry->name = "$requestMethod $resourceName";
                $RegistryEntry->accessDeniedCode = $accessDeniedCode;
                $RegistryEntry->classId = $classId;
                $RegistryEntry->lastModifiedTime = $lastModifiedTime;
                $RegistryEntries[] = $RegistryEntry;
              }
            }
            break;
        }
      }
    }
    return $RegistryEntries;
  }
  
  /**
   * Create DOMNode and populate with registry entry data .
   *
   * @param DOMDocument $Document Registry entry will be exported to this DOM Document.
   * @param DOMElement $Parent Registry entry will be appended to this DOM Element.
   *
   * @return DOMElement Exported element or NULL.
   */
  public function export(DOMDocument $Document, DOMElement $Parent) {
    //
    // @todo: export [connector] registry entry.
    //
  }
  
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey) {
    
    $RegistryEntry = NULL;
    
    $primaryKey = self::validatePrimaryKey($primaryKey);
    if ($primaryKey) {
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
        where(sprintf("`id` = '%s'", $primaryKey));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_Connector::create($registrationInfo[0]);
        }
      }
    }
    return $RegistryEntry;
  }
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames() {
    return array(0 => 'id');
  }
  
  /**
   * Update or insert registration record.
   *
   * If the encapsulated registration exists, based on id property, it will be updated
   * to reflect object state. Otherwise, a new registration record will be created.
   *
   * @return AblePolecat_Command_Result or NULL.
   */
  public function save() {
    $sql = __SQL()->          
      replace(
        'id', 
        'name', 
        'resourceId', 
        'requestMethod', 
        'accessDeniedCode', 
        'classId',
        'lastModifiedTime')->
      into('connector')->
      values(
        $this->getId(), 
        $this->getName(), 
        $this->getResourceId(),
        $this->getRequestMethod(),
        $this->getAccessDeniedCode(),
        $this->getClassId(),
        $this->getLastModifiedTime()
      );
    return $this->executeDml($sql);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ConnectorInterface.
   ********************************************************************************/
  
  /**
   * @return string.
   */
  public function getResourceId() {
    return $this->getPropertyValue('resourceId');
  }
  
  /**
   * @return string
   */
  public function getRequestMethod() {
    return $this->getPropertyValue('requestMethod');
  }
  
  /**
   * @return int.
   */
  public function getAccessDeniedCode() {
    return $this->getPropertyValue('accessDeniedCode');
  }
  
  /**
   * @return string.
   */
  public function getClassId() {
    return $this->getPropertyValue('classId');
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}