<?php
/**
 * @file: AccessControl.php
 * Manages access control for Able Polecat server.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Role.php')));
require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Environment.php');
// include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'CacheObject.php');
// include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

class AblePolecat_AccessControl extends AblePolecat_CacheObjectAbstract {
  
  /**
   * @var AblePolecat_AccessControl Instance of singleton.
   */
  private static $AccessControl;
  
  /**
   * @var Array() Registry of active access control agents.
   */
  private $Agents;
  
  /**
   * Extends __construct().
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->Agents = array();
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
   * Return access control agent for given environment context.
   *
   * @param AblePolecat_EnvironmentInterface The environment in context.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent(AblePolecat_EnvironmentInterface $Environment) {
    
    $Agent = NULL;
    $class_name = get_class($Environment);
    
    if (isset($this->Agents[$class_name])) {
      $Agent = $this->Agents[$class_name];
    }
    else {
      switch ($class_name) {
        default:
          break;
        case 'AblePolecat_Environment_Server':
          $agentClassName = 'AblePolecat_AccessControl_Agent_Server';
          break;
        case 'AblePolecat_Environment_Application':
          $agentClassName = 'AblePolecat_AccessControl_Agent_Application';
          break;
        case 'AblePolecat_Environment_User':
          $agentClassName = 'AblePolecat_AccessControl_Agent_User';
          break;
      }
      $reg = AblePolecat_Server::getClassRegistry()->registerLoadableClass($agentClassName, NULL, 'wakeup');
      $Agent = AblePolecat_Server::getClassRegistry()->loadClass($agentClassName);
      isset($Agent) ? $this->Agents[$class_name] = $Agent : NULL;
    }
    if (!isset($Agent)) {
      $error_msg = sprintf("No access control agent defined for %s.", get_class($Environment));
      throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
    }
    return $Agent;
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
    if (!isset(self::$AccessControl)) {
      self::$AccessControl = new AblePolecat_AccessControl();
      //
      // @todo: load access control matrix
      //
    }
    return self::$AccessControl;
  }
}

/**
 * Exceptions thrown by Able Polecat Access Control objects.
 */
class AblePolecat_AccessControl_Exception extends AblePolecat_Exception {
}
