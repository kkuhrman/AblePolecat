<?php
/**
 * @file: Resource.php
 * The access control 'object', some resource secured by constraints which agents may 
 * seek to gain access to; e.g. a file, a device, a database connection, etc.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Constraint.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Subject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Resource', 'Locater.php')));

interface AblePolecat_AccessControl_ResourceInterface extends AblePolecat_AccessControl_ArticleInterface {
  
  /**
   * Sets given constraint on the resource.
   *
   * By default, setting constraint on this resource denies this action to all 
   * agents and roles. 
   *
   * @param AblePolecat_AccessControl_ConstraintInterface $Constraint.
   *
   * @return bool TRUE if constraint is set, otherwise FALSE.
   * 
   * @see setPermission().
   */
  public function setConstraint(AblePolecat_AccessControl_ConstraintInterface $Constraint);
  
  /**
   * Sets permission for given agent or role.
   *
   * In actuality, unless a constraint is set on the resource, all agents and roles 
   * have permission for corresponding action. If constraint is set, setPermission 
   * simply exempts agent or role from that constraint (i.e. 'unblocks' them).
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Agent or role.
   * @param string $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   *
   * @return bool TRUE if permission is granted, otherwise FALSE.
   */
  public function setPermission(AblePolecat_AccessControl_SubjectInterface $Subject, $constraint_id);
  
  /**
   * Verifies that agent or role has given permission.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Agent or role.
   * @param string $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   *
   * @return bool TRUE if agent or role has permission, otherwise FALSE.
   */
  public function hasPermission(AblePolecat_AccessControl_SubjectInterface $Subject, $constraint_id);
}

abstract class AblePolecat_AccessControl_ResourceAbstract implements AblePolecat_AccessControl_ResourceInterface {
  
  /**
   * Constraint data keys.
   */
  const CONSTRAINT_INFO   = 'info';
  const CONSTRAINT_PERM   = 'permissions';
  
  /**
   * @var Constraints assigned to resource.
   */
  private $m_Constraints;
  
  /**
   * @var AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource if any.
   */
  protected $m_Locater;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should implement to initialize members in __construct().
   */
  abstract protected function initialize();
  
  /**
   * Sets given constraint on the resource.
   *
   * By default, setting constraint on this resource denies this action to all 
   * agents and roles. 
   *
   * @param AblePolecat_AccessControl_ConstraintInterface $Constraint.
   *
   * @return bool TRUE if constraint is set, otherwise FALSE.
   * 
   * @see setPermission().
   */
  public function setConstraint(AblePolecat_AccessControl_ConstraintInterface $Constraint) {
    
    $result = TRUE;
    if (!isset($this->m_Constraints[$Constraint->getId()])) {
      $this->m_Constraints[$Constraint->getId()] = array(
        self::CONSTRAINT_INFO => $Constraint,
        self::CONSTRAINT_PERM => array(),
      );
    }
    return $result;
  }
  
  /**
   * Sets permission for given agent or role.
   *
   * In actuality, unless a constraint is set on the resource, all agents and roles 
   * have permission for corresponding action. If constraint is set, setPermission 
   * simply exempts agent or role from that constraint (i.e. 'unblocks' them).
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Agent or role.
   * @param string $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   *
   * @return bool TRUE if permission is granted, otherwise FALSE.
   */
  public function setPermission(AblePolecat_AccessControl_SubjectInterface $Subject, $constraint_id) {
    
    $result = TRUE;
    
    //
    // If constraint is not set, agent/role has permission by default.
    //
    if (isset($this->m_Constraints[$constraint_id])) {
      if (!isset($this->m_Constraints[$constraint_id][self::CONSTRAINT_PERM][$Subject->getId()])) {
        $this->m_Constraints[$constraint_id][self::CONSTRAINT_PERM][$Subject->getId()] = $Subject->getName();
      }
    }
    return $result;
  }
  
  /**
   * Verifies that agent or role has given permission.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Agent or role.
   * @param string $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   *
   * @return bool TRUE if agent or role has permission, otherwise FALSE.
   */
  public function hasPermission(AblePolecat_AccessControl_SubjectInterface $Subject, $constraint_id) {
    
    $result = FALSE;
    // $this->logMessage($constraint_id, AblePolecat_LogInterface::STATUS, $this->m_Constraints[$constraint_id][self::CONSTRAINT_PERM]);
    
    //
    // If constraint is not set, agent/role has permission by default.
    //
    if (!isset($this->m_Constraints[$constraint_id])) {
      $result = TRUE;
    }
    else if (isset($this->m_Constraints[$constraint_id][self::CONSTRAINT_PERM][$Subject->getId()])) {
      $result = TRUE;
    }
    if (!$result) {
      //
      // log failed permission check
      //
      $Constraint = $this->m_Constraints[$constraint_id][self::CONSTRAINT_INFO];
      $message = sprintf("'%s' permission not granted to agent '%s' on '%s'",
        $Constraint->getName(),
        $Subject->getName(),
        $this->getName()
      );
      $this->logMessage($message);
    }
    return $result;
  }
  
  /**
   * Sets URL used to open resource.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Locater.
   */
  protected function setLocater(AblePolecat_AccessControl_Resource_LocaterInterface $Locater) {
    $this->m_Locater = $Locater;
  }
  
  /**
   * @return AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource or NULL.
   */
  public function getLocater() {
    return $this->m_Locater;
  }
  
  /**
   * @see initialize().
   */
  final protected function __construct() {
    $this->m_Constraints = array();
    $this->m_Locater = NULL;
    $this->initialize();
  }
}