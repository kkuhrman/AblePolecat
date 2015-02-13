<?php
/**
 * @file      Database.php
 * @brief     Base class for Able Polecat database clients.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource', 'Locater', 'Dsn.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Schema.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Database.php')));

interface AblePolecat_DatabaseInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * @return AblePolecat_AccessControl_Resource_Locater_DsnInterface URL used to open resource or NULL.
   */
  public function getLocater();
  
  /**
   * Install database objects for given schema.
   *
   * @param AblePolecat_Database_SchemaInterface $Schema
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public function install(AblePolecat_Database_SchemaInterface $Schema);
  
  /**
   * Execute SQL DML and return number of rows effected.
   * 
   * NOTE: USE execute() for INSERT, DELETE, UPDATE, REPLACE.
   *       USE query() for SELECT.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql.
   *
   * @return Array Results/rowset.
   * @see query()
   */
  public function execute(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql);
  
  /**
   * Execute SQL DML and return results as an array.
   * 
   * NOTE: USE query() for SELECT.
   *       USE execute() for INSERT, DELETE, UPDATE, REPLACE.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql.
   *
   * @return Array Results/rowset.
   * @see execute()
   */
  public function query(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql);
}

abstract class AblePolecat_DatabaseAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_DatabaseInterface {

  /**
   * @var AblePolecat_AccessControl_Resource_Locater_DsnInterface URL used to open resource if any.
   */
  protected $Locater;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public function getName() {
    
    $name = NULL;
    if (isset($this->Locater)) {
      $name = $this->Locater->getPathname();
    }
    return $name;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_DatabaseInterface.
   ********************************************************************************/
  
  /**
   * @return AblePolecat_AccessControl_Resource_Locater_DsnInterface URL used to open resource or NULL.
   */
  public function getLocater() {
    return $this->Locater;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Sets URL used to open resource.
   *
   * @param AblePolecat_AccessControl_Resource_Locater_DsnInterface $Locater.
   */
  protected function setLocater(AblePolecat_AccessControl_Resource_Locater_DsnInterface $Locater) {
    $this->Locater = $Locater;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->Locater = NULL;
  }
}