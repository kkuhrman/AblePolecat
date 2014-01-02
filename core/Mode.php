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

abstract class AblePolecat_ModeAbstract implements AblePolecat_ModeInterface {
  
  /**
   * @var Next reverse target in command chain of responsibility.
   */
  private $Superior;
  
  /**
   * @var Next forward target in command chain of responsibility.
   */
  private $Subordinate;
  
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
    
    if (isset($this->Superior)) {
      $Result = $this->Superior->assignRoleToAgent($Authority, $agent_id, $role_id);
    }
    else {
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
    
    if (isset($this->Superior)) {
      $Result = $this->Superior->authorizeRoleForAgent($Authority, $agent_id, $role_id);
    }
    else {
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
    
    if (isset($this->Superior)) {
      $Result = $this->Superior->grantPermission($Authority, $subject_id, $constraint_id, $resource_id);
    }
    else {
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
    
    if (isset($this->Superior)) {
      $Result = $this->Superior->hasPermission($Authority, $subject_id, $constraint_id, $resource_id);
    }
    else {
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
    
    if (isset($this->Superior)) {
      $Result = $this->Superior->setConstraint($Authority, $constraint_id, $resource_id);
    }
    else {
      $message = "Could not set constraint(s) because access control authority is not available.";
      throw new AblePolecat_AccessControl_Exception($message);
    }
    
    return $Result;
  }
  
  /********************************************************************************
   * Command target methods.
   ********************************************************************************/
  
  /**
   * Validates given command target as a forward or reverse COR link.
   *
   * @param AblePolecat_Command_TargetInterface $Target.
   * @param string $direction 'forward' | 'reverse'
   *
   * @return bool TRUE if proposed COR link is acceptable, otherwise FALSE.
   */
  abstract protected function validateCommandLink(AblePolecat_Command_TargetInterface $Target, $direction);
  
  /**
   * Send command or forward or back the chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  protected function delegateCommand(AblePolecat_CommandInterface $Command) {
    
    $Result = new AblePolecat_Command_Result();
    $Target = NULL;
    
    $direction = NULL;
    if (is_a($Command, 'AblePolecat_Command_ForwardInterface')) {
      $direction = AblePolecat_Command_TargetInterface::CMD_LINK_FWD;
    }
    if (is_a($Command, 'AblePolecat_Command_ReverseInterface')) {
      $direction = AblePolecat_Command_TargetInterface::CMD_LINK_REV;
    }
    
    switch ($direction) {
      default:
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_FWD:
        $Target = $this->getForwardCommandLink();
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        $Target = $this->getReverseCommandLink();
        break;
    }
    if (isset($Target)) {
      $Result = $Target->execute($Command);
    }
    return $Result;
  }
  
  /**
   * Get forward (subordinate) link in command-processing Chain of Responsibility.
   *
   * @return AblePolecat_Command_TargetInterface $Target 
   */
  protected function getForwardCommandLink() {
    return $this->Subordinate;
  }
  
  /**
   * Get reverse (superior) link in command-processing Chain of Responsibility.
   *
   * @return AblePolecat_Command_TargetInterface $Target 
   */
  protected function getReverseCommandLink() {
    return $this->Superior;
  }
  
  /**
   * Allow given subject to serve as direct subordinate in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended subordinate target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setForwardCommandLink(AblePolecat_Command_TargetInterface $Target) {
    
    $Super = NULL;
    
    if ($this->validateCommandLink($Target, AblePolecat_Command_TargetInterface::CMD_LINK_FWD)) {
      $Super = $this;
      $this->Subordinate = $Target;
    }
    else {
      $msg = sprintf("Attempt to set %s as forward command link to %s was refused.",
        get_class($Target),
        get_class($this)
      );
      throw new AblePolecat_Command_Exception($msg);
    }
    return $Super;
  }
  
  /**
   * Allow given subject to serve as direct superior in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended superior target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setReverseCommandLink(AblePolecat_Command_TargetInterface $Target) {
    
    $Subordinate = NULL;
    
    //
    // Only application mode can serve as next in COR.
    //
    if ($this->validateCommandLink($Target, AblePolecat_Command_TargetInterface::CMD_LINK_REV)) {
      $Subordinate = $this;
      $this->Superior = $Target;
    }
    else {
      $msg = sprintf("Attempt to set %s as forward command link to %s was refused.",
        get_class($Target),
        get_class($this)
      );
      throw new AblePolecat_Command_Exception($msg);
    }
    return $Subordinate;
  }
  
  /********************************************************************************
   * Error/exceptional handling methods.
   ********************************************************************************/
  
  /**
   * Handle errors triggered by child objects.
   */
  public static function handleError($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL) {
    
    $Result = NULL;
    
    if (isset($this->Superior)) {
      $Result = $this->Superior->handleError($errno, $errstr, $errfile, $errline, $errcontext);
    }
    else {
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
    
    if (isset($this->Superior)) {
      $Result = $this->Superior->handleException($Exception);
    }
    else {
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
      $this->Superior = $args[0];
    }
    else {
      $this->Superior = NULL;
    }
    $this->Subordinate = NULL;
    
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