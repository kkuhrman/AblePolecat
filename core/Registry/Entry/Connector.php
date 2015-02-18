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

require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Install.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Update.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Util.php')));
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
   * @return string.
   */
  public function getTransactionClassName();
  
  /**
   * @return string.
   */
  public function getAuthorityClassName();
  
  /**
   * @return int.
   */
  public function getAccessDeniedCode();
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
    //
    // @todo: import [connector] registry entry.
    //
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
    
    if (is_array($primaryKey) && (2 == count($primaryKey))) {
      isset($primaryKey['resourceId']) ? $resourceId = $primaryKey['resourceId'] : $resourceId = $primaryKey[0];
      isset($primaryKey['requestMethod']) ? $requestMethod = $primaryKey['requestMethod'] : $requestMethod = $primaryKey[1];
      
      $sql = __SQL()->          
          select(
            'resourceId', 
            'requestMethod', 
            'transactionClassName', 
            'authorityClassName', 
            'accessDeniedCode')->
          from('connector')->
          where(sprintf("`resourceId` = '%s' AND `requestMethod` = '%s'", $resourceId, $requestMethod));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        if (isset($classInfo[0])) {
          $ConnectorRegistration = new AblePolecat_Registry_Entry_Connector();
          $ConnectorRegistration->resourceId = $classInfo[0]['resourceId'];
          $ConnectorRegistration->requestMethod = $classInfo[0]['requestMethod'];
          $ConnectorRegistration->transactionClassName = $classInfo[0]['transactionClassName'];
          $ConnectorRegistration->authorityClassName = $classInfo[0]['authorityClassName'];
          $ConnectorRegistration->accessDeniedCode = $classInfo[0]['accessDeniedCode'];
        }
      }
    }
    else {
      throw new AblePolecat_Registry_Exception('Invalid Primary Key passed to ' . __METHOD__);
    }
    
    return $ConnectorRegistration;
  }
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames() {
    return array(0 => 'resourceId', 1 => 'requestMethod');
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
    //
    // @todo: complete REPLACE [connector]
    //
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
   * @return string.
   */
  public function getTransactionClassName() {
    return $this->getPropertyValue('transactionClassName');
  }
  
  /**
   * @return string.
   */
  public function getAuthorityClassName() {
    return $this->getPropertyValue('authorityClassName');
  }
  
  /**
   * @return int.
   */
  public function getAccessDeniedCode() {
    return $this->getPropertyValue('accessDeniedCode');
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