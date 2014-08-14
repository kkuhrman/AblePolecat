<?php
/**
 * @file      polecat/core/AccessControl/Agent.php
 * @brief     The access control 'subject'; for example, a user.
 *
 * Intended to follow interface specified by W3C but does not provide public access to 
 * properties (get/set methods provided).
 *
 * @see http://www.w3.org/TR/url/#url
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');

interface AblePolecat_AccessControl_AgentInterface extends AblePolecat_AccessControl_SubjectInterface {
}

abstract class AblePolecat_AccessControl_AgentAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_AgentInterface {
  
  /**
   * @var Array[AblePolecat_AccessControl_RoleInterface].
   */
  private $ActiveRoles;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_AgentInterface.
   ********************************************************************************/
  
  /**
   * Assign given role to agent on authority of subject.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Authority Access control authority granting role.
   * @param AblePolecat_AccessControl_RoleInterface $Role Assigned role.
   *
   * @throw AblePolecat_AccessControl_Exception If role is not assignable to agent.
   */
  public function assignActiveRole(
    AblePolecat_AccessControl_SubjectInterface $Authority, 
    AblePolecat_AccessControl_RoleInterface $Role) {
    
    $authorized = FALSE;
    
    if (isset($Authority) && is_a($Authority, 'AblePolecat_AccessControl')) {
      if ($Authority->agentAuthorizedForRole($this->getId(), $Role->getId())) {
        if (!isset($this->ActiveRoles[$Role->getId()])) {
          $this->ActiveRoles[$Role->getId()] = $Role;
        }
        $authorized = TRUE;
      }
    }
    if (!$authorized) {
      AblePolecat_AccessControl::throwDenyAccessException($this, $Role, $Authority);
    }
    return $authorized;
  }
  
  /**
   * Return active role by id.
   *
   * @param string $roleId The ID of the active role, if assigned.
   *
   * @return mixed Instance of AblePolecat_AccessControl_RoleInterface, otherwise FALSE.
   */
  public function getActiveRole($roleId) {
    
    $Role = FALSE;
    if (isset($roleId) && is_scalar($roleId) && isset($this->ActiveRoles[$roleId])) {
      $Role = $this->ActiveRoles[$roleId];
    }
    return $Role;
  }
  
  /**
   * Returns a list of IDs of all assigned, active roles.
   *
   * @return Array.
   */
  public function getActiveRoleIds() {
    return array_keys($this->ActiveRoles);
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
    
  /**
   * Extends __construct().
   */
  protected function initialize() {
    
    $this->ActiveRoles = array();
  }
}