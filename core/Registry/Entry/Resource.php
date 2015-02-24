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
    return new AblePolecat_Registry_Entry_Resource();
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
    // @todo: import [resource] registry entry.
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
    
    $ResourceRegistration = NULL;
    
    if (self::validatePrimaryKey($primaryKey)) {
      isset($primaryKey['id']) ? $id = $primaryKey['id'] : $id = $primaryKey;
      
      $sql = __SQL()->          
        select(
          'id', 
          'name', 
          'hostName', 
          'classId', 
          'lastModifiedTime')->
        from('resource')->
        where(sprintf("`id` = '%s'", $id));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        if (isset($classInfo[0])) {
          $ResourceRegistration = new AblePolecat_Registry_Entry_Resource();
          isset($registrationInfo[0]['id']) ? $ResourceRegistration->id = $registrationInfo[0]['id'] : NULL;
          isset($registrationInfo[0]['name']) ? $ResourceRegistration->name = $registrationInfo[0]['name'] : NULL;
          isset($registrationInfo[0]['hostName']) ? $ResourceRegistration->hostName = $registrationInfo[0]['hostName'] : NULL;
          isset($registrationInfo[0]['classId']) ? $ResourceRegistration->classId = $registrationInfo[0]['classId'] : NULL;
          isset($registrationInfo[0]['lastModifiedTime']) ? $ResourceRegistration->lastModifiedTime = $registrationInfo[0]['lastModifiedTime'] : NULL;
        }
      }
    }
    return $ResourceRegistration;
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
      insert(
        'id', 
        'name', 
        'hostName', 
        'classId', 
        'lastModifiedTime')->
      into('class')->
      values(
        $this->getId(), 
        $this->getName(), 
        $this->getHostName(), 
        $this->getClassId(), 
        $this->getLastModifiedTime()
      );
    $this->executeDml($sql, $Database);
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