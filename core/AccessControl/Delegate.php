<?php
/**
 * @file      polecat/AccessControl/Delegate.php
 * @brief     An object to which certain access control responsibilities have been delegated.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));

interface AblePolecat_AccessControl_DelegateInterface extends AblePolecat_AccessControl_SubjectInterface {
  
  /**
   * Assign given role(s) to agent(s).
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Authority Access control subject making the request.
   * @param mixed $agent_id i.e. AblePolecat_AccessControl_AgentInterface::getId() or Array of Ids.
   * @param string $role_id i.e. AblePolecat_AccessControl_RoleInterface::getId() or Array of Ids.
   * 
   * @throw AblePolecat_AccessControl_Exception if any role cannot be assigned to given agent(s)
   */
  public function assignRoleToAgent(
    AblePolecat_AccessControl_SubjectInterface $Authority,
    $agent_id,
    $role_id
  );
  
  /**
   * Authorize agent(s) to assume given role(s).
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Authority Access control subject making the request.
   * @param mixed $agent_id i.e. AblePolecat_AccessControl_AgentInterface::getId() or Array of Ids.
   * @param string $role_id i.e. AblePolecat_AccessControl_RoleInterface::getId() or Array of Ids.
   * 
   * @throw AblePolecat_AccessControl_Exception if any role cannot be authorized for given agent(s).
   */
   public function authorizeRoleForAgent(
    AblePolecat_AccessControl_SubjectInterface $Authority,
    $agent_id,
    $role_id
  );
   
  /**
   * Grants permission (removes constraint) to given agent or role.
   *
   * In actuality, unless a constraint is set on the resource, all agents and roles 
   * have permission for corresponding action. If constraint is set, grant() 
   * simply exempts agent or role from that constraint (i.e. 'unblocks' them).
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Authority Access control subject making the request.
   * @param mixed $subject_id i.e. AblePolecat_AccessControl_SubjectInterface::getId() or Array of Ids.
   * @param mixed $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   * @param mixed $resource_id i.e. AblePolecat_AccessControl_ResourceInterface::getId() or Array of ids.
   *
   * @throw AblePolecat_AccessControl_Exception if any permission cannot be granted to given agent/role(s).
   */
  public function grantPermission(
    AblePolecat_AccessControl_SubjectInterface $Authority,
    $subject_id, 
    $constraint_id, 
    $resource_id
  );
  
  /**
   * Verifies that agent or role has given permission.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Authority Access control subject making the request.
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Agent or role.
   * @param string $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   * @param string $resource_id i.e. AblePolecat_AccessControl_ResourceInterface::getId().
   *
   * @return bool TRUE if $Subject has given permission, otherwise FALSE.
   * @throw AblePolecat_AccessControl_Exception if $Authority is not privileged to access permissions.
   */
  public function hasPermission(
    AblePolecat_AccessControl_SubjectInterface $Authority,
    AblePolecat_AccessControl_SubjectInterface $Subject, 
    $constraint_id, 
    $resource_id
  );
  
  /**
   * Sets given constraint on the resource.
   *
   * By default, setting constraint on this resource denies this action to all 
   * agents and roles. 
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Authority Access control subject making the request.
   * @param mixed $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   * @param mixed $resource_id i.e. AblePolecat_AccessControl_ResourceInterface::getId() or Array of ids.
   *
   * @return bool TRUE if constraint is set, otherwise FALSE.
   * @throw AblePolecat_AccessControl_Exception if any constraint(s) cannot be set on given resource(s).
   */
  public function setConstraint(
    AblePolecat_AccessControl_SubjectInterface $Authority,
    $constraint_id, 
    $resource_id
  ); 
}