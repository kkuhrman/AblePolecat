<?php
/**
 * @file      polecat/core/AccessControl/Authority.php
 * @brief     Places constraints on resources and grants permission to agents/roles.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Static.php')));

interface AblePolecat_AccessControl_AuthorityInterface extends AblePolecat_AccessControl_Article_StaticInterface {
  
  /**
   * Verify that agent is authorized to assume given role.
   *
   * @param string $agentId ID of agent.
   * @param string $roleId ID of role.
   *
   * @return bool TRUE if role is authorized for agent, otherwise FALSE.
   */
  public function agentAuthorizedForRole($agentId, $roleId);
  
  /**
   * Assign agent to given role.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent
   * @param AblePolecat_AccessControl_RoleInterface $Role
   * 
   * @return bool TRUE if agent is assigned to role, otherwise FALSE.
   */
  public function assignRole(
    AblePolecat_AccessControl_AgentInterface $Agent, 
    AblePolecat_AccessControl_RoleInterface $Role
  );
  
  /**
   * Authorize role for given agent.
   *
   * @param AblePolecat_AccessControl_RoleInterface $Role
   * @param AblePolecat_AccessControl_AgentInterface $Agent
   * 
   * @return bool TRUE if role is authorized for agent, otherwise FALSE.
   */
   public function authorizeRole(
    AblePolecat_AccessControl_RoleInterface $Role, 
    AblePolecat_AccessControl_AgentInterface $Agent
  );
   
  /**
   * Grants permission (removes constraint) to given agent or role.
   *
   * In actuality, unless a constraint is set on the resource, all agents and roles 
   * have permission for corresponding action. If constraint is set, grant() 
   * simply exempts agent or role from that constraint (i.e. 'unblocks' them).
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Agent or role.
   * @param AblePolecat_AccessControl_ConstraintInterface $Constraint.
   *
   * @return bool TRUE if permission is granted, otherwise FALSE.
   */
  public function grantPermission(
    AblePolecat_AccessControl_SubjectInterface $Subject, 
    AblePolecat_AccessControl_ConstraintInterface $Constraint
  );
}