<?php
/**
 * @file      polecat/core/Registry/Entry/Template.php
 * @brief     Encapsulates record of a template registered in [template].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_TemplateInterface extends AblePolecat_Registry_EntryInterface {  
  /**
   * @return string.
   */
  public function getArticleId();
  
  /**
   * @return string.
   */
  public function getDocType();
  
  /**
   * @return string.
   */
  public function getDefaultHeaders();
  
  /**
   * @return string.
   */
  public function getFullPath();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Template extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_TemplateInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * PHP magic method is run when writing data to inaccessible properties.
   *
   * @param string $name  Name of property to set.
   * @param mixed  $value Value to assign to given property.
   */
  public function __set($name, $value) {
    
    if ($name == 'fullPath') {
      $this->fileStat = stat($value);
      if ($this->fileStat && isset($this->fileStat['mtime'])) {
        parent::__set('lastModifiedTime', $this->fileStat['mtime']);
      }
      else {
        AblePolecat_Command_Chain::triggerError("Failed to retrieve file stats on $value.");
      }
    }
    parent::__set($name, $value);
  }
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_Template();
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
    // @todo: import [template] registry entry.
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
    // @todo: export [template] registry entry.
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
    
    $TemplateRegistration = NULL;
    
    if (is_array($primaryKey) && (1 == count($primaryKey))) {
      //
      // Create registry object and initialize primary key.
      //
      $TemplateRegistration = new AblePolecat_Registry_Entry_Template();
      isset($primaryKey['id']) ? $TemplateRegistration->id = $primaryKey['id'] : $TemplateRegistration->id = $primaryKey[0];
      
      //
      // Generate and execute SELECT statement.
      //
      $sql = __SQL()->          
        select('id', 'name', 'articleId', 'docType', 'defaultHeaders', 'fullPath', 'lastModifiedTime')->
        from('template')->
        where(sprintf("`id` = '%s'", $TemplateRegistration->id));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          isset($registrationInfo[0]['name']) ? $TemplateRegistration->name = $registrationInfo[0]['name'] : NULL;
          isset($registrationInfo[0]['articleId']) ? $TemplateRegistration->articleId = $registrationInfo[0]['articleId'] : NULL;
          isset($registrationInfo[0]['docType']) ? $TemplateRegistration->docType = $registrationInfo[0]['docType'] : NULL;
          isset($registrationInfo[0]['defaultHeaders']) ? $TemplateRegistration->defaultHeaders = $registrationInfo[0]['defaultHeaders'] : NULL;
          isset($registrationInfo[0]['fullPath']) ? $TemplateRegistration->fullPath = $registrationInfo[0]['fullPath'] : NULL;
          isset($registrationInfo[0]['lastModifiedTime']) ? $TemplateRegistration->lastModifiedTime = $registrationInfo[0]['lastModifiedTime'] : NULL;
        }
      }
    }
    return $TemplateRegistration;
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
        'articleId',
        'docType', 
        'defaultHeaders', 
        'fullPath',
        'lastModifiedTime')->
      into('template')->
      values(
        $this->getId(),
        $this->getName(),
        $this->getArticleId(), 
        $this->getDocType(), 
        $this->getDefaultHeaders(), 
        $this->getFullPath(),
        $this->getLastModifiedTime(), 
      );
    return $this->executeDml($sql, $Database);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_TemplateInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getArticleId() {
    return $this->getPropertyValue('articleId');
  }
  
  /**
   * @return string.
   */
  public function getDocType() {
    return $this->getPropertyValue('docType');
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
  public function getFullPath() {
    return $this->getPropertyValue('fullPath');
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