<?php
/**
 * @file: AccessControl.php
 * Manages access control for Able Polecat server.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Role.php')));
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'CacheObject.php');
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

interface AblePolecat_AccessControlInterface extends AblePolecat_CacheObjectInterface {
  
  /**
   * Assign agent to given role.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent
   * @param AblePolecat_AccessControl_RoleInterface $Role
   * 
   * @return bool TRUE if agent is assigned to role, otherwise FALSE.
   */
  public function assign(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_AccessControl_RoleInterface $Role);
  
  /**
   * Authorize role for given agent.
   *
   * @param AblePolecat_AccessControl_RoleInterface $Role
   * @param AblePolecat_AccessControl_AgentInterface $Agent
   * 
   * @return bool TRUE if role is authorized for agent, otherwise FALSE.
   */
   public function authorize(AblePolecat_AccessControl_RoleInterface $Role, AblePolecat_AccessControl_AgentInterface $Agent);
   
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
  public function grant(AblePolecat_AccessControl_SubjectInterface $Subject, AblePolecat_AccessControl_ConstraintInterface $Constraint);
}

class AblePolecat_AccessControl extends AblePolecat_CacheObjectAbstract {
  
  /**
   * Extends __construct().
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
  }
  
  /**
   * Assign agent to given role.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent
   * @param AblePolecat_AccessControl_RoleInterface $Role
   * 
   * @return bool TRUE if agent is assigned to role, otherwise FALSE.
   */
  public function assign(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_AccessControl_RoleInterface $Role) {
    //
    // @TODO:
    //
    return TRUE;
  }
  
  /**
   * Authorize role for given agent.
   *
   * @param AblePolecat_AccessControl_RoleInterface $Role
   * @param AblePolecat_AccessControl_AgentInterface $Agent
   * 
   * @return bool TRUE if role is authorized for agent, otherwise FALSE.
   */
   public function authorize(AblePolecat_AccessControl_RoleInterface $Role, AblePolecat_AccessControl_AgentInterface $Agent) {
    //
    // @TODO:
    //
    return TRUE;
   }
   
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
  public function grant(AblePolecat_AccessControl_SubjectInterface $Subject, AblePolecat_AccessControl_ConstraintInterface $Constraint) {
    //
    // @TODO:
    //
    return TRUE;
  }
  
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
   * @return AblePolecat_AccessControl Initialized access control service or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    $AccessControl = new AblePolecat_AccessControl();
    return $AccessControl;
  }
}

/**
 * Exceptions thrown by Able Polecat Access Control objects.
 */
class AblePolecat_AccessControl_Exception extends AblePolecat_Exception {
  /**
   * Error codes for access control.
   */
  const ERROR_ACCESS_DENIED             = 0x00000001; // Catch-all, non-specific access denied error.
  const ERROR_ACCESS_ROLE_NOT_AUTH      = 0x00000010; // Agent could not be assigned to given role.
  const ERROR_ACCESS_ROLE_DENIED        = 0x00000020; // Role denied access to resource.
}
