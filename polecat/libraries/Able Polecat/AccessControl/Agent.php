<?php
/**
 * @file: Agent.php
 * Base class for Able Polecat access control agents (subject).
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');

abstract class AblePolecat_AccessControl_AgentAbstract implements AblePolecat_AccessControl_AgentInterface {
  
  /**
   * @var Roles assigned to agent.
   */
  private $m_Roles;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize members in __construct().
   * If Over-riding always call parent::initialize() first.
   */
  protected function initialize() {
  }
  
  /**
   * Assign the given role to the agent.
   *
   * @param object Object implementing AblePolecat_AccessControl_RoleInterface.
   *
   * @return bool TRUE if the role is assigned to agent, otherwise FALSE.
   */
  public function assignRole(AblePolecat_AccessControl_RoleInterface $Role) {
    
    $response = FALSE;
    // $rid = $Role->getId();
    if ($Role->isAuthorized($this) && !isset($this->m_Roles[$Role->getId()])) {
      $this->m_Roles[$Role->getId()] = $Role;
    }
    return $response;
  }
  
  /**
   * Return roles assigned to agent.
   *
   * @return Array Zero or more instances of class implementing AblePolecat_AccessControl_RoleInterface.
   */
  public function getRoles() {
    return $this->m_Roles;
  }
  
  /**
   * @see initialize().
   */
  final protected function __construct() {
    $this->m_Roles = array();
    $this->initialize();
  }
}