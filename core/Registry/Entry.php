<?php
/**
 * @file      polecat/core/Registry/Entry.php
 * @brief     Encapsulates a record in one of the Able Polecat core registries.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Dynamic.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'DynamicObject.php');

interface AblePolecat_Registry_EntryInterface 
  extends AblePolecat_AccessControl_Article_DynamicInterface, AblePolecat_DynamicObjectInterface {
  
  /**
   * Create the registry entry object and populate with given DOMNode data.
   *
   * @param DOMNode $Node DOMNode encapsulating registry entry.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function import(DOMNode $Node);
  
  /**
   * Create DOMNode and populate with registry entry data .
   *
   * @param DOMDocument $Document Registry entry will be exported to this DOM Document.
   * @param DOMElement $Parent Registry entry will be appended to this DOM Element.
   *
   * @return DOMElement Exported element or NULL.
   */
  public function export(DOMDocument $Document, DOMElement $Parent);
  
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey);
  
  /**
   * @return int Typically the last modified date of the object source file.
   */
  public function getLastModifiedTime();
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames();
  
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
  public function save(AblePolecat_DatabaseInterface $Database = NULL);
  
  /**
   * Validates given value against primary key schema.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return boolean TRUE if given value meets schema requirements for PK, otherwise FALSE.
   */
  public static function validatePrimaryKey($primaryKey = NULL);
}

abstract class AblePolecat_Registry_EntryAbstract extends AblePolecat_DynamicObjectAbstract implements AblePolecat_Registry_EntryInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Scope of operation.
   *
   * @return string SYSTEM.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
   
  /**
   * @return UUID Universally unique identifier of registry object.
   */
  public function getId() {
    return $this->getPropertyValue('id');
  }
  
  /**
   * @return string Common name of registry object.
   */
  public function getName() {
    return $this->getPropertyValue('name');
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * @return int Typically the last modified date of the object source file.
   */
  public function getLastModifiedTime() {
    return $this->getPropertyValue('lastModifiedTime');
  }
  
  /**
   * Validates given value against primary key schema.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return boolean TRUE if given value meets schema requirements for PK, otherwise FALSE.
   */
  public static function validatePrimaryKey($primaryKey = NULL) {
    
    $validPrimaryKeyValue = FALSE;
    if (isset($primaryKey) && is_array($primaryKey) && isset($primaryKey['id'])) {
        $validPrimaryKeyValue = TRUE;
    }
    else if (is_string($primaryKey) && (36 === strlen($primaryKey))) {
      $validPrimaryKeyValue = TRUE;
    }
    return $validPrimaryKeyValue;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Execute DML to save registry entry to database.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql.
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @return Array Results/rowset.
   */
  protected function executeDml(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql,
    AblePolecat_DatabaseInterface $Database) {
    $Result = NULL;
    if (isset($Database)) {
      $Result = $Database->execute($sql);
    }
    else {
      $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success()) {
        $Result = $CommandResult->value();
      }
    }
    return $Result;
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    $this->lastModifiedTime = 0;
  }
}