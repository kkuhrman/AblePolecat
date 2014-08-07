<?php
/**
 * @file      polecat/AccessControl/Delegate/System.php
 * @brief     An object to which certain access control responsibilities have been delegated.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Delegate.php')));

abstract class AblePolecat_AccessControl_Delegate_SystemAbstract implements AblePolecat_AccessControl_DelegateInterface {
  
  /**
   * Constraint data keys.
   */
  const CONSTRAINT_INFO   = 'info';         // Information about the specific constraint.
  const CONSTRAINT_RES    = 'resource';     // Resource on which constraint is placed.
  const CONSTRAINT_PERM   = 'permissions';  // Subjects exempt from given constraint on specific resource.
  const CONSTRAINT_AUTH   = 'authority';    // Authority placing constraint, granting permission, etc.
  
  /**
   * @var Constraints assigned to resource.
   */
  private $m_Constraints;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_DelegateInterface
   ********************************************************************************/
  
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
  ) {
    //
    // @todo:
    //
  }
  
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
  ) {
    //
    // @todo:
    //
  }
  
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
  ) {
    
    //
    // @todo: validate authority
    //
    
    //
    // First check if given constraint is placed on resource.
    //
    if (isset($this->m_Constraints[$constraint_id][self::CONSTRAINT_RES][$resource_id])) {
      $this->m_Constraints[$constraint_id][self::CONSTRAINT_RES][$resource_id][self::CONSTRAINT_PERM][$Subject::getId()] =
        array(self::CONSTRAINT_AUTH => $Authority::getId());
    }
  }
  
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
  ) {
    //
    // @todo: validate authority
    //
    $hasPermission = TRUE;
    
    //
    // First check if given constraint is placed on resource.
    //
    if (isset($this->m_Constraints[$constraint_id][self::CONSTRAINT_RES][$resource_id])) {
      $hasPermission = isset($this->m_Constraints[$constraint_id][self::CONSTRAINT_RES][$resource_id][self::CONSTRAINT_PERM][$Subject::getId()]);
    }
    
    return $hasPermission;
  }
  
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
  ) {
    
    //
    // @todo: validate authority
    //
    $constraints = self::putArguments($constraint_id);
    $resources = self::putArguments($resource_id);

    foreach ($constraints as $key => $constraint) {
      if (!isset($this->m_Constraints[$constraint])) {
        $this->m_Constraints[$constraint] = array(
          self::CONSTRAINT_INFO => self::lookupConstraint($this, $constraint),
          self::CONSTRAINT_RES  => array(),
        );
      }
      if (!isset($this->m_Constraints[$constraint][self::CONSTRAINT_RES][$resource_id])) {
        $this->m_Constraints[$constraint][self::CONSTRAINT_RES][$resource_id] = array(
          self::CONSTRAINT_AUTH => $Authority::getId(),
          self::CONSTRAINT_PERM => array(),
        );
      }
    }
  }
  
  /********************************************************************************
   Helper functions.
   ********************************************************************************/
  
  /** 
   * Looks up a constraint based on given id.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Authority Access control subject making the request.
   * @param mixed $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   *
   * @return AblePolecat_AccessControl_ConstraintInterface or NULL.
   * @throw AblePolecat_AccessControl_Exception if $Authority is not permitted to lookup constraints.
   */
  public static function lookupConstraint(
    AblePolecat_AccessControl_SubjectInterface $Authority,
    $constraint_id
  ) {
    //
    // @todo:
    //
    return NULL;
  }
  
  /**
   * Helper function accepts string or array as input and returns array of string(s).
   * @throw AblePolecat_Command_Exception 
   */
  private static function putArguments($arguments) {
    
    $output = NULL;
    $invalid_type = 'NULL';
    
    if (isset($arguments)) {
      if (is_array($arguments)) {
        $output = array();
        foreach($arguments as $key => $argument)
          if (is_string($argument)) {
            $output[] = $argument;
          }
          else {
            $invalid_type = get_type($argument);
            $output = NULL;
          }
      }
      else if (is_string($arguments)) {
        $output = array($arguments);
      }
      else {
        $invalid_type = get_type($arguments);
      }
    }
    if (!isset($output)) {
      $message = "Access Control article id must be string. $invalid_type given.";
      throw new AblePolecat_Command_Exception($message);
    }
    return $output;
  }
}