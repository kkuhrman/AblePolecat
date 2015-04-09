<?php
/**
 * @file      polecat/core/Registry/Entry/Resource.php
 * @brief     Encapsulates record of a resource registered in [resource].
 *
 * Addressability of resources is achieved by enforcing uniqueness of each
 * host name + path combination. In Able Polecat, path, as used in previous 
 * sentence, is the same as resource name (not necessarily unique except in
 * combination with host name to comprise URL).
 *
 * @see Richardson/Ruby, RESTful Web Services (ISBN 978-0-596-52926-0)
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ResourceInterface extends AblePolecat_Registry_EntryInterface {
  
  /**
   * @return string
   */
  public function getHostName();
  
  /**
   * @return string.
   */
  public function getClassId();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Resource extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ResourceInterface {
  
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
    $RegistryEntry = new AblePolecat_Registry_Entry_Resource();
    
    //
    // Check method arguments for database record.
    //
    $args = func_get_args();
    if (isset($args[0]) && is_array($args[0])) {
      $Record = $args[0];
      isset($Record['id']) ? $RegistryEntry->id = $Record['id'] : NULL;
      isset($Record['name']) ? $RegistryEntry->name = $Record['name'] : NULL;
      isset($Record['hostName']) ? $RegistryEntry->hostName = $Record['hostName'] : NULL;
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
    
    if (is_a($Node, 'DOMElement') && ($Node->tagName == 'polecat:resourceClass') && $Node->hasChildNodes()) {
      //
      // host name
      //
      static $hostName;
      $hostName = AblePolecat_Host::getRequest()->getHostName();
      
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
        $message = sprintf("resource class %s references invalid class id %s.",
          $className,
          $classId
        );
        $RegistryEntry = NULL;
        AblePolecat_Command_Chain::triggerError($message);
      }
      
      //
      // Check for resources already registered.
      // Unique key is name + hostName.
      //
      $registeredResources = array($hostName => array());
      $sql = __SQL()->          
        select(
          'id', 
          'name', 
          'hostName', 
          'classId', 
          'lastModifiedTime')->
        from('resource')->
        where(sprintf("`classId` = '%s'", $classId));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        foreach ($registrationInfo as $key => $Record) {
          if ($hostName == $Record['hostName']) {
            $name = $Record['name'];
            $registeredResources[$hostName][$name] = AblePolecat_Registry_Entry_Resource::create($Record);
          }
        }
      }
      
      foreach($Node->childNodes as $key => $ResourceNode) {
        switch ($ResourceNode->nodeName) {
          default:
            break;
          case 'polecat:resource':
            $registerFlag = $ResourceNode->getAttribute('register');
            if ($registerFlag != '0') {
              //
              // Check if resource is already registered.
              //
              $RegistryEntry = NULL;
              $name = $ResourceNode->getAttribute('id');
              if (isset($registeredResources[$hostName][$name])) {
                $RegistryEntry = $registeredResources[$hostName][$name];
              }
              else {
                $RegistryEntry = AblePolecat_Registry_Entry_Resource::create();
                $RegistryEntry->id = self::generateUUID();
                $RegistryEntry->name = $name;
                $RegistryEntry->hostName = $hostName;
                $RegistryEntry->classId = $classId;
              }
              
              //
              // NOTE: the resource URI path is in the 'id' attribute for 
              // resource elements encapsulated by the resourceClass element.
              //
              $RegistryEntry->lastModifiedTime = $lastModifiedTime;
              $RegistryEntries[] = $RegistryEntry;
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
    // @todo: export [resource] registry entry.
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
          'hostName', 
          'classId', 
          'lastModifiedTime')->
        from('resource')->
        where(sprintf("`id` = '%s'", $primaryKey));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_Resource::create($registrationInfo[0]);
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
    return array(0 => 'resourceId');
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
        'hostName', 
        'classId', 
        'lastModifiedTime')->
      into('resource')->
      values(
        $this->getId(), 
        $this->getName(), 
        $this->getHostName(), 
        $this->getClassId(), 
        $this->getLastModifiedTime()
      );
    return $this->executeDml($sql, $Database);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ResourceInterface.
   ********************************************************************************/
  
  /**
   * @return string
   */
  public function getHostName() {
    return $this->getPropertyValue('hostName');
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