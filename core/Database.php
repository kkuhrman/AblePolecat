<?php
/**
 * @file      Database.php
 * @brief     Base class for Able Polecat database clients.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource', 'Locater', 'Dsn.php')));
require_once(ABLE_POLECAT_CORE. DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Database.php')));

interface AblePolecat_DatabaseInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * Opens an existing resource or makes an empty one accessible depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking access.
   * @param AblePolecat_AccessControl_Resource_Locater_DsnInterface $Url Existing or new resource.
   * @param string $name Optional common name for new resources.
   *
   * @return bool TRUE if access to resource is granted, otherwise FALSE.
   */
  public function open(AblePolecat_AccessControl_AgentInterface $Agent = NULL, AblePolecat_AccessControl_Resource_Locater_DsnInterface $Url = NULL);
  
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
  
  /**
   * Sets URL used to open resource.
   *
   * @param AblePolecat_AccessControl_Resource_Locater_DsnInterface $Locater.
   */
  protected function setLocater(AblePolecat_AccessControl_Resource_Locater_DsnInterface $Locater) {
    $this->Locater = $Locater;
  }
  
  /**
   * @return AblePolecat_AccessControl_Resource_Locater_DsnInterface URL used to open resource or NULL.
   */
  public function getLocater() {
    return $this->Locater;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->Locater = NULL;
  }
}