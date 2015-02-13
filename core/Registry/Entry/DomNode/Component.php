<?php
/**
 * @file      polecat/core/Registry/Entry/DomNode/Component.php
 * @brief     Encapsulates record of a component registered in [component].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode.php')));

interface AblePolecat_Registry_Entry_ComponentInterface extends AblePolecat_Registry_Entry_DomNodeInterface {
  
  /**
   * @return string.
   */
  public function getComponentId();
  
  /**
   * @return int.
   */
  public function getComponentClassName();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_DomNode_Component extends AblePolecat_Registry_Entry_DomNodeAbstract implements AblePolecat_Registry_Entry_ComponentInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_DomNode_Component();
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
    
    $ComponentRegistration = NULL;
    
    if (is_array($primaryKey) && (1 == count($primaryKey))) {
      $ComponentRegistration = new AblePolecat_Registry_Entry_DomNode_Component();
      isset($primaryKey['componentId']) ? $ComponentRegistration->componentId = $primaryKey['componentId'] : $ComponentRegistration->componentId = $primaryKey[0];
      
      $sql = __SQL()->          
        select(
          'docType', 
          'componentClassName', 
          'templateFullPath', 
          'lastModifiedTime')->
        from('component')->
        where(sprintf("`componentId` = '%s'", $ComponentRegistration->componentId));
      $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $Records = $CommandResult->value();
        if (isset($Records[0])) {
          $Record = $Records[0];
          $ComponentRegistration->unserializeDocType($Record['docType']);
          $ComponentRegistration->componentClassName = $Record['componentClassName'];
          $ComponentRegistration->templateFullPath = $Record['templateFullPath'];
          $ComponentRegistration->lastModifiedTime = $Record['lastModifiedTime'];
        }
      }
    }
    return $ComponentRegistration;
  }
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames() {
    return array(0 => 'componentId');
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
        'componentId', 
        'docType', 
        'componentClassName',
        'templateFullPath', 
        'lastModifiedTime')->
      into('component')->
      values(
        $this->getComponentId(), 
        $this->serializeDocType(), 
        $this->getComponentClassName(),
        $this->getTemplateFullPath(), 
        $this->getLastModifiedTime()
      );
    return $this->executeDml($sql, $Database);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ComponentInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getComponentId() {
    return $this->getPropertyValue('componentId');
  }
  
  /**
   * @return string.
   */
  public function getComponentClassName() {
    return $this->getPropertyValue('componentClassName');
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