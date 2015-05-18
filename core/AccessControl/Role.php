<?php
/**
 * @file      polecat/core/AccessControl/Role.php
 * @brief     A job function within the system such as 'anonymous', 'authenticated', 'administrator' etc.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');

interface AblePolecat_AccessControl_RoleInterface extends AblePolecat_AccessControl_SubjectInterface, AblePolecat_CacheObjectInterface {
  /**
   * Verify that given agent is authorized to be assigned role.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   *
   * @throw AblePolecat_AccessControl_Exception if agent is not authorized for role.
   */
  public function isAuthorized(AblePolecat_AccessControl_AgentInterface $Agent);
}

abstract class AblePolecat_AccessControl_RoleAbstract extends AblePolecat_AccessControl_SubjectAbstract implements AblePolecat_AccessControl_RoleInterface {
  
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
    //
    // System user is granted 'default allow' on all roles.
    //
    if (!is_a($Agent, 'AblePolecat_AccessControl_Agent_User_System')) {
      throw new AblePolecat_AccessControl_Exception(sprintf("%s is not authorized for %s role.",
        $Agent->getName(),
        $this->getName()
      ));
    }
    return TRUE;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}