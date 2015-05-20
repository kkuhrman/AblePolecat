<?php
/**
 * @file      Database.php
 * @brief     Base class for Able Polecat database clients.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource', 'Locater', 'Dsn.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Schema.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Database.php')));

interface AblePolecat_DatabaseInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * DSN Property constants.
   */
  const DSN_FULL    = 'dsn';
  const DSN_USER    = 'user';
  const DSN_PASS    = 'pass';
  const DSN_HOST    = 'host';
  const DSN_PORT    = 'port';
  const DSN_NAME    = 'database';
  const LOCATER     = 'locater';
  
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
  
  /**
   * Indicates whether database connection is established and accessible.
   *
   * @return boolean TRUE if database connection is functional, otherwise FALSE.
   */
  public function ready();
}

abstract class AblePolecat_DatabaseAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_DatabaseInterface {
    
  /**
   * @var AblePolecat_AccessControl_Resource_Locater_DsnInterface URL used to open resource if any.
   */
  protected $Locater;
  
  /**
   * @var string Database name.
   */
  private $name;
  
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
    return $this->name;
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
   * Extract user name, password, database name, host name etc. from agent role.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   *
   * @return mixed Array with DSN settings or NULL.
   */
  protected function extractDsnSettingsFromAgentRole(AblePolecat_AccessControl_AgentInterface $Agent) {
    
    $dsnSettings = NULL;
    
    $dbClientRole = $Agent->getActiveRole(AblePolecat_AccessControl_Role_Client_Database::ROLE_ID);
    if ($dbClientRole) {
      $Url = $dbClientRole->getResourceLocater();
      $AccessControlToken = $dbClientRole->getAccessControlToken();
      if (isset($Url) && isset($AccessControlToken)) {
        $dsnSettings = array();
        $dsnSettings[self::DSN_FULL] = $Url->getDsn();
        $dsnSettings[self::DSN_NAME] = trim($Url->getPathname(), AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH);
        $dsnSettings[self::DSN_HOST] = $Url->getHostname();
        $dsnSettings[self::DSN_PORT] = $Url->getPort();
        $dsnSettings[self::DSN_USER] = $AccessControlToken->getUsername();
        $dsnSettings[self::DSN_PASS] = $AccessControlToken->getPassword();
        $dsnSettings[self::LOCATER]  = $Url;
      }
    }
    else {
      throw new AblePolecat_AccessControl_Exception(sprintf("%s is not authorized for %s role.",
        $Agent->getName(),
        AblePolecat_AccessControl_Role_Client_Database::ROLE_NAME
      ));
    }
    return $dsnSettings;
  }
  
  /**
   * Extract user name, password, database name, host name etc. from locater.
   *
   * @param AblePolecat_AccessControl_Resource_Locater_DsnInterface $Locater.
   *
   * @return mixed Array with DSN settings or NULL.
   */
  protected function extractDsnSettingsFromLocater(AblePolecat_AccessControl_Resource_Locater_DsnInterface $Locater) {
    
    $dsnSettings = array();
    $dsnSettings[self::DSN_FULL] = $Locater->getDsn();
    $dsnSettings[self::DSN_NAME] = trim($Locater->getPathname(), AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH);
    $dsnSettings[self::DSN_HOST] = $Locater->getHostname();
    $dsnSettings[self::DSN_PORT] = $Locater->getPort();
    $dsnSettings[self::DSN_USER] = $Locater->getUsername();
    $dsnSettings[self::DSN_PASS] = $Locater->getPassword();
    $dsnSettings[self::LOCATER]  = $Locater;
    return $dsnSettings;
  }
  
  /**
   * Sets URL used to open resource.
   *
   * @param AblePolecat_AccessControl_Resource_Locater_DsnInterface $Locater.
   */
  protected function setLocater(AblePolecat_AccessControl_Resource_Locater_DsnInterface $Locater) {
    $this->Locater = $Locater;
    $this->name = trim($this->Locater->getPathname(), AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH);
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->Locater = NULL;
    $this->name = NULL;
  }
}