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
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Install.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Update.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Util.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Get', 'Resource.php')));
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
    return new AblePolecat_Registry_Entry_Connector();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * Create the registry entry object and populate with given DOMNode data.
   *
   * @param DOMNode $Node DOMNode encapsulating registry entry.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function import(DOMNode $Node) {
    
    $ConnectorRegistration = AblePolecat_Registry_Entry_Connector::create();
    $ConnectorRegistration->id = $Node->getAttribute('id');
    $ConnectorRegistration->name = $Node->getAttribute('name');
    $ConnectorRegistration->requestMethod = $Node->getAttribute('requestMethod');
    $ConnectorRegistration->accessDeniedCode = $Node->getAttribute('accessDeniedCode');
    foreach($Node->childNodes as $key => $childNode) {
      switch ($childNode->nodeName) {
        default:
          break;
        case 'polecat:resourceId':
          $ConnectorRegistration->resourceId = $childNode->nodeValue;
          break;
        case 'polecat:classId':
          $ConnectorRegistration->classId = $childNode->nodeValue;
          break;
      }
    }
    
    //
    // Verify class reference.
    //
    $ClassRegistration = AblePolecat_Registry_Class::wakeup()->
      getRegistrationById($ConnectorRegistration->getClassId());
    if (isset($ClassRegistration)) {
      $ConnectorRegistration->lastModifiedTime = $ClassRegistration->lastModifiedTime;
    }
    else {
      $message = sprintf("connector %s (%s) references invalid class %s.",
        $ConnectorRegistration->getName(),
        $ConnectorRegistration->getId(),
        $ConnectorRegistration->getClassId()
      );
      $ConnectorRegistration = NULL;
      AblePolecat_Command_Chain::triggerError($message);
    }
    return $ConnectorRegistration;
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
    
    $ConnectorRegistration = NULL;
    
    if (self::validatePrimaryKey($primaryKey)) {
      isset($primaryKey['id']) ? $id = $primaryKey['id'] : $id = $primaryKey;
      
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
        where(sprintf("`id` = '%s'", $id));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          $ConnectorRegistration = new AblePolecat_Registry_Entry_Connector();
          isset($registrationInfo[0]['id']) ? $ConnectorRegistration->id = $registrationInfo[0]['id'] : NULL;
          isset($registrationInfo[0]['name']) ? $ConnectorRegistration->name = $registrationInfo[0]['name'] : NULL;
          isset($registrationInfo[0]['resourceId']) ? $ConnectorRegistration->resourceId = $registrationInfo[0]['resourceId'] : NULL;
          isset($registrationInfo[0]['requestMethod']) ? $ConnectorRegistration->requestMethod = $registrationInfo[0]['requestMethod'] : NULL;
          isset($registrationInfo[0]['accessDeniedCode']) ? $ConnectorRegistration->accessDeniedCode = $registrationInfo[0]['accessDeniedCode'] : NULL;
          isset($registrationInfo[0]['classId']) ? $ConnectorRegistration->classId = $registrationInfo[0]['classId'] : NULL;
          isset($registrationInfo[0]['lastModifiedTime']) ? $ConnectorRegistration->lastModifiedTime = $registrationInfo[0]['lastModifiedTime'] : NULL;
        }
      }
    }
    return $ConnectorRegistration;
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
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function save(AblePolecat_DatabaseInterface $Database = NULL) {
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
    return $this->executeDml($sql, $Database);
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