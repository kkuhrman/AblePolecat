<?php
/**
 * @file: Mode.php
 * A link in the command processing chain of responsibility.
 *
 * Able Polecat Modes are similar to OS protection rings, in terms of access control,
 * but serve also as an implementation of the chain of responsibility (COR) design 
 * pattern, either processing a command or passing it to the next, higher level of 
 * responsibility in the the chain/hierarchy.
 *
 * The simplest COR hierarchy in Able Polecat:
 * Server Mode - Receives HTTP request, sends HTTP response
 * Application Mode - Handles interaction between Able Polecat objects via class methods. 
 * Session Mode - Handles interaction between Able Polecat objects and web server session state.
 *
 * Important responsibilities of the Mode class:
 * 1. Handle errors and exceptions.
 * 2. Encapsulate environment configuration settings.
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Delegate', 'System.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Target.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Mode.php')));

interface AblePolecat_ModeInterface 
  extends AblePolecat_AccessControl_DelegateInterface,
          AblePolecat_CacheObjectInterface,
          AblePolecat_Command_TargetInterface {
  
  /**
   * Handle access control violations.
   */
  // public static function handleAccessControlViolation();
  
  /**
   * Handle errors triggered by child objects.
   */
  public static function handleError($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL);
  
  /**
   * Handle exceptions thrown by child objects.
   */
  public static function handleException(Exception $Exception);
  
}

abstract class AblePolecat_ModeAbstract extends AblePolecat_Command_TargetAbstract implements AblePolecat_ModeInterface {
  
  /********************************************************************************
   * Access control methods.
   * Overriding these provides opportunity to handle delegated tasks. Otherwise,
   * responsibility should be passed to reverse link (upstream) to higher authority.
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
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->assignRoleToAgent($Authority, $agent_id, $role_id);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = "Could not assign role(s) to agent(s) because access control authority is not available.";
      throw new AblePolecat_AccessControl_Exception($message);
    }
    
    return $Result;
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
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->authorizeRoleForAgent($Authority, $agent_id, $role_id);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = "Could not authorize role(s) for agent(s) because access control authority is not available.";
      throw new AblePolecat_AccessControl_Exception($message);
    }
    
    return $Result;
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
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->grantPermission($Authority, $subject_id, $constraint_id, $resource_id);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = "Could not grant requested permission(s) because access control authority is not available.";
      throw new AblePolecat_AccessControl_Exception($message);
    }
    
    return $Result;
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
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->hasPermission($Authority, $subject_id, $constraint_id, $resource_id);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = "Could not validate permission(s) because access control authority is not available.";
      throw new AblePolecat_AccessControl_Exception($message);
    }
    
    return $Result;
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
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->setConstraint($Authority, $constraint_id, $resource_id);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = "Could not set constraint(s) because access control authority is not available.";
      throw new AblePolecat_AccessControl_Exception($message);
    }
    
    return $Result;
  }
  
  /********************************************************************************
   * Error/exceptional handling methods.
   ********************************************************************************/
  
  /**
   * Handle errors triggered by child objects.
   */
  public static function handleError($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL) {
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->handleError($errno, $errstr, $errfile, $errline, $errcontext);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = sprintf("Error [%d] in Able Polecat. %s No command target was able to intercept.",
        $errno, $errstr
      );
      trigger_error($message, E_USER_ERROR);
    }
    
    return $Result;
  }
  
  /**
   * Handle exceptions thrown by child objects.
   */
  public static function handleException(Exception $Exception) {
    
    $Result = NULL;
    
    try {
      $Result = $this->getReverseCommandLink()->handleException($Exception);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $message = sprintf("Unhandled exception [%d] in Able Polecat. %s No command target was able to intercept.",
        $Exception->getCode(), 
        $Exception->getMessage()
      );
      throw new AblePolecat_Mode_Exception($message);
    }
    
    return $Result;
  }
  /********************************************************************************
   * Create/destroy methods
   ********************************************************************************/
   
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  abstract protected function initialize();
  
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    
    //
    // Process constructor arguments
    //
    $args = func_get_args();
    if (isset($args[0]) && is_a($args[0], 'AblePolecat_ModeInterface')) {
      $this->setReverseCommandLink($args[0]);
    }
    
    //
    // Initialize sub-class members.
    //
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}