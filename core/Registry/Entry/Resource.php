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
  public function getResourceName();
  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return string.
   */
  public function getResourceClassName();
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
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey) {
    
    $ResourceRegistration = NULL;
    
    if (is_array($primaryKey) && (1 == count($primaryKey))) {
      $ResourceRegistration = new AblePolecat_Registry_Entry_Resource();
      isset($primaryKey['resourceId']) ? $ResourceRegistration->resourceId = $primaryKey['resourceId'] : $ResourceRegistration->resourceId = $primaryKey[0];
      
      $sql = __SQL()->          
          select(
            'resourceId', 
            'hostName', 
            'resourceName', 
            'resourceClassName', 
            'lastModifiedTime')->
          from('resource')->
          where(sprintf("`resourceId` = '%s'", $ResourceRegistration->resourceId));
      $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        if (isset($classInfo[0])) {
          $ResourceRegistration->hostName = $classInfo[0]['hostName'];
          $ResourceRegistration->resourceName = $classInfo[0]['resourceName'];
          $ResourceRegistration->resourceClassName = $classInfo[0]['resourceClassName'];
          $ResourceRegistration->lastModifiedTime = $classInfo[0]['lastModifiedTime'];
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
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function save() {
    //
    // @todo: complete REPLACE [resource]
    //
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
  public function getResourceName() {
    return $this->getPropertyValue('resourceName');
  }
  
  /**
   * @return string.
   */
  public function getResourceId() {
    return $this->getPropertyValue('resourceId');
  }
  
  /**
   * @return string.
   */
  public function getResourceClassName() {
    return $this->getPropertyValue('resourceClassName');
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