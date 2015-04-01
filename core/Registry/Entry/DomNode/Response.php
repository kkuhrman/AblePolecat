<?php
/**
 * @file      polecat/core/Registry/Entry/DomNode/Response.php
 * @brief     Encapsulates record of a resource registered in [response].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode.php')));

interface AblePolecat_Registry_Entry_DomNode_ResponseInterface extends AblePolecat_Registry_Entry_DomNodeInterface {
  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return int.
   */
  public function getStatusCode();
  
  /**
   * @return string.
   */
  public function getDefaultHeaders();
  
  /**
   * @return Array.
   */
  public function getClassId();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_DomNode_Response extends AblePolecat_Registry_Entry_DomNodeAbstract implements AblePolecat_Registry_Entry_DomNode_ResponseInterface {
  
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
    $RegistryEntry = new AblePolecat_Registry_Entry_DomNode_Response();
    
    //
    // Check method arguments for database record.
    //
    $args = func_get_args();
    if (isset($args[0]) && is_array($args[0])) {
      $Record = $args[0];
      isset($Record['id']) ? $RegistryEntry->id = $Record['id'] : NULL;
      isset($Record['name']) ? $RegistryEntry->name = $Record['name'] : NULL;
      isset($Record['resourceId']) ? $RegistryEntry->resourceId = $Record['resourceId'] : NULL;
      isset($Record['statusCode']) ? $RegistryEntry->statusCode = $Record['statusCode'] : NULL;
      isset($Record['defaultHeaders']) ? $RegistryEntry->defaultHeaders = $Record['defaultHeaders'] : NULL;
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
    
    if (is_a($Node, 'DOMElement') && ($Node->tagName == 'polecat:responseClass') && $Node->hasChildNodes()) {
      //
      // Verify class reference.
      //
      $className = $Node->getAttribute('name');
      $classId = $Node->getAttribute('id');
      $lastModifiedTime = 0;
      $ClassRegistration = AblePolecat_Registry_Class::wakeup()->
        getRegistrationById($classId);
      if (isset($ClassRegistration)) {
        $lastModifiedTime = $ClassRegistration->lastModifiedTime;
      }
      else {
        $message = sprintf("response class %s references invalid class id %s.",
          $className,
          $classId
        );
        $RegistryEntry = NULL;
        AblePolecat_Command_Chain::triggerError($message);
      }
      
      foreach($Node->childNodes as $key => $childNode) {
        //
        // @todo: add default headers to <polecat:response>
        //
        switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:resourceGroups':
            $resourceGroups = self::importResourceGroups($childNode);
            foreach($resourceGroups as $resourceGroupKey => $resourceGroup) {
              //
              // @todo: HTTP response status code validation.
              //
              $statusCode = 0;
              if(isset($resourceGroup['attributes'])) {
                $statusCode = intval($resourceGroup['attributes']->getNamedItem('statusCode')->value);
              }
              foreach($resourceGroup['resources'] as $resourceId => $ResourceNode) {
                $RegistryEntry = AblePolecat_Registry_Entry_DomNode_Response::create();
                $RegistryEntry->id = self::generateUUID();
                $RegistryEntry->name = $ResourceNode->getAttribute('name');
                $RegistryEntry->resourceId = $resourceId;
                $RegistryEntry->statusCode = $statusCode;
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
    // @todo: export [response] registry entry.
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
          'statusCode',
          'defaultHeaders', 
          'classId', 
          'lastModifiedTime')->
        from('response')->
        where(sprintf("`id` = '%s'", $primaryKey));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_DomNode_Response::create($registrationInfo[0]);
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
        'statusCode',
        'defaultHeaders', 
        'classId',
        'lastModifiedTime')->
      into('response')->
      values(
        $this->getId(), 
        $this->getName(), 
        $this->getResourceId(),
        $this->getStatusCode(),
        $this->getDefaultHeaders(), 
        $this->getClassId(),
        $this->getLastModifiedTime()
      );
    return $this->executeDml($sql, $Database);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_DomNode_ResponseInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getResourceId() {
    return $this->getPropertyValue('resourceId');
  }
  
  /**
   * @return int.
   */
  public function getStatusCode() {
    return $this->getPropertyValue('statusCode');
  }
  
  /**
   * @return string.
   */
  public function getDefaultHeaders() {
    return $this->getPropertyValue('defaultHeaders');
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