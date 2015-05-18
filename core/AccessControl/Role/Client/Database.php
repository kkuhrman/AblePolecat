<?php
/**
 * @file      polecat/core/AccessControl/Role/Client/Database.php
 * @brief     Base class for database client role.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Token', 'Dsn.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role', 'Client.php')));

interface AblePolecat_AccessControl_Role_Client_DatabaseInterface extends AblePolecat_AccessControl_Role_ClientInterface {
}

class AblePolecat_AccessControl_Role_Client_Database 
  extends AblePolecat_AccessControl_Role_ClientAbstract
  implements AblePolecat_AccessControl_Role_Client_DatabaseInterface {
  
  /**
   * System user id and name.
   */
  const ROLE_ID   = '37e5774b-fd96-11e4-b890-0050569e00a2';
  const ROLE_NAME = 'Database Client';
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    $Role = new AblePolecat_AccessControl_Role_Client_Database();
    $User = AblePolecat_AccessControl_Agent_User::wakeup();
    $Role->isAuthorized($Subject);
    return $Role;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_RoleInterface.
   ********************************************************************************/
  
  /**
   * Verify that given agent is authorized to be assigned role.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   *
   * @throw AblePolecat_AccessControl_Exception if agent is not authorized for role.
   */
  public function isAuthorized(AblePolecat_AccessControl_AgentInterface $Agent) {
    
    $isAuthorized = FALSE;
    
    if (is_a($Agent, 'AblePolecat_AccessControl_Agent_User')) {
      $isAuthorized = TRUE;
    }
    else {
      $isAuthorized = parent::isAuthorized($Agent);
    }
    return $isAuthorized;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Role_ClientInterface.
   ********************************************************************************/
  
  /**
   * Set security token granting access to service.
   *
   * @param AblePolecat_AccessControl_TokenInterface $AccessControlToken.
   */
  public function setAccessControlToken(AblePolecat_AccessControl_TokenInterface $AccessControlToken) {
    if (is_a()) {
      parent::setAccessControlToken($AccessControlToken);
    }
    else {
      throw new AblePolecat_AccessControl_Exception(sprintf("Access control token for %s must implement %s. %s passed.",
        __CLASS__,
        'AblePolecat_AccessControl_Token_DsnInterface',
        AblePolecat_Data::getDataTypeName($AccessControlToken)
      ));
    }
  }
  
  /**
   * Set locater to service.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $ResourceLocater.
   */
  public function setResourceLocater(AblePolecat_AccessControl_Resource_LocaterInterface $ResourceLocater) {
    if (is_a($ResourceLocater, 'AblePolecat_AccessControl_Resource_Locater_Dsn')) {
      parent::setResourceLocater($ResourceLocater);
      
      //
      // If resource locater contains user name and password, we eliminate need 
      // to pass those separately via setAccessControlToken().
      //
      $dbUser = $ResourceLocater->getUsername();
      $dbPass = $ResourceLocater->getPassword();
      if (isset($dbUser) && isset($dbPass)) {
        $AccessControlToken = AblePolecat_AccessControl_Token_Dsn::create($dbUser, $dbPass);
        parent::setAccessControlToken($AccessControlToken);
      }
    }
    else {
      throw new AblePolecat_AccessControl_Exception(sprintf("Resource locater for %s must implement %s. %s passed.",
        __CLASS__,
        'AblePolecat_AccessControl_Resource_Locater_Dsn',
        AblePolecat_Data::getDataTypeName($ResourceLocater)
      ));
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->setId(self::ROLE_ID);
    $this->setName(self::ROLE_NAME);
  }
}