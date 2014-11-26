<?php
/**
 * @file      polecat/core/Transaction/AccessControl/Authority.php
 * @brief     Manages the most basic interactive agent authentication by way of database user(s).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Authority.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role', 'User', 'Authenticated.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction.php')));

interface AblePolecat_Transaction_AccessControl_AuthorityInterface extends AblePolecat_AccessControl_AuthorityInterface {
}

class AblePolecat_Transaction_AccessControl_Authority 
  extends AblePolecat_TransactionAbstract 
  implements AblePolecat_Transaction_AccessControl_AuthorityInterface
{
  
  /**
   * Access control id Constants.
   */
  const UUID = '736735cc-44b6-11e4-b353-0050569e00a2';
  const NAME = 'Able Polecat Access Control Authority';
  
  const ARG_USER = 'user';
  const ARG_PASS = 'pass';
  const ARG_AUTH = 'authority';
  
  /**
   * @var AblePolecat_AccessControl_Agent_User Instance of singleton.
   */
  private static $Transaction;
  
  /**
   * @var Array Permissions granted to user on core database.
   */
  private $grants;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for agent.
   *
   * @return string Transaction identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for agent.
   *
   * @return string Transaction name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Transaction)) {
      //
      // Unmarshall (from numeric keyed index to named properties) variable args list.
      //
      $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
      self::$Transaction = new AblePolecat_Transaction_AccessControl_Authority($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
      self::prepare(self::$Transaction, $ArgsList, __FUNCTION__);
      
    }
    return self::$Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
   
  /**
   * Commit
   */
  public function commit() {
    //
    // Parent updates transaction in database.
    //
    parent::commit();
  }
  
  /**
   * Return a request authentication or authentication attempt result resource.
   *
   * @return AblePolecat_ResourceInterface
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function run() {
    
    $Resource = NULL;
    
    switch ($this->getRequest()->getMethod()) {
      default:
        //
        // @todo: this is probably not good
        //
        break;
      case 'GET':
        $Resource = AblePolecat_Resource_Core_Factory::wakeup(
          $this->getAgent(),
          'AblePolecat_Resource_Core_Form'
        );
        $Resource->addText('Enter user name and password for database administrator.');
        $Resource->addControl('label', array('for' => 'userName'), 'Username: ');
        $Resource->addControl('input', array('id' => 'userName', 'type' => 'text', 'name' => self::ARG_USER));
        $Resource->addControl('label', array('for' => 'passWord'), 'Password: ');
        $Resource->addControl('input', array('id' => 'passWord', 'type' => 'password', 'name' => self::ARG_PASS));
        // $Resource->addControl('input', array('id' => 'filePath', 'type' => 'file', 'name' => 'filePath')); // @todo: for something other
        break;
      case 'POST':
        //
        // @see request base class analyzeRequestPath() method.
        // 
        $postData = $this->getRequest()->getRequestQueryString(FALSE);
        isset($postData[self::ARG_USER][0]) ? $user = $postData[self::ARG_USER][0] : $user = NULL;
        isset($postData[self::ARG_PASS][0]) ? $pass = $postData[self::ARG_PASS][0] : $pass = NULL;
        $CommandResult = AblePolecat_Command_AccessControl_Authenticate::invoke($this->getAgent(), $user, $pass);
        if($CommandResult->success()) {
          //
          // Establish what permissions the given user has on core database.
          //
          $this->setGrants($CommandResult->value());
          if (count($this->grants)) {
            //
            // Assign agent to authenticated role.
            //
            $Role = AblePolecat_AccessControl_Role_User_Authenticated::wakeup(
              $this->getAgent(),
              $this->getId()
            );
            $this->assignRole($this->getAgent(), $Role);
            
            //
            // Assign permissions to role.
            //
            foreach ($this->grants as $grantNumber => $constraintClassName) {
              $constraintClass = $this->getClassRegistry()->loadClass($constraintClassName);
              $this->grantPermission($this->getAgent(), $constraintClass);
            }
          }
          
          //
          // Refresh agent state.
          //
          $this->getAgent()->refreshActiveRoles();
          
          //
          // Flag transaction for commit.
          //
          $this->setStatus(self::TX_STATE_COMPLETED);
        }
        
        //
        // Return control to parent transaction
        //
        $Resource = $this->getParent()->run();
        break;
    }
    
    return $Resource;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_AuthorityInterface.
   ********************************************************************************/
  
  /**
   * Verify that agent is authorized to assume given role.
   *
   * @param string $agentId ID of agent.
   * @param string $roleId ID of role.
   *
   * @return bool TRUE if role is authorized for agent, otherwise FALSE.
   */
  public function agentAuthorizedForRole($agentId, $roleId) {
    
    //
    // @todo:
    //
    $authorized = TRUE;
    return $authorized;
  }
  
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
  ) {
    
    $result = FALSE;
    
    $sql = __SQL()->
      insert('sessionNumber', 'roleId', 'userId', 'roleData')->
      into('role')->
      values(AblePolecat_Host::getSessionNumber(), $Role->getId(), 0, '');
    $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getAgent(), $sql);
    if ($CommandResult->success()) {
      $result = TRUE;
    }
    return $result;
  }
  
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
  ) {
    return FALSE;
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
  public function grantPermission(
    AblePolecat_AccessControl_SubjectInterface $Subject, 
    AblePolecat_AccessControl_ConstraintInterface $Constraint
  ) {
    $result = FALSE;
    
    $sql = __SQL()->
      insert('sessionNumber', 'resourceId', 'constraintId', 'subjectId', 'authorityId')->
      into('permission')->
      values(AblePolecat_Host::getSessionNumber(), $this->getResourceRegistration()->getResourceId(), $Constraint::getId(), $Subject->getId(), $this->getId());
    $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getAgent(), $sql);
    if ($CommandResult->success()) {
      $result = TRUE;
    }
    return $result;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Rollback
   */
  public function rollback() {
    //
    // @todo
    //
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Expects results from SHOW GRANTS sql and populates with corresponding constraint ids.
   */
  private function setGrants($grants) {
    if (is_array($grants)) {
      if (isset($grants['polecat']['*']['ALL PRIVILEGES'])) {
        $this->grants[] = 'AblePolecat_AccessControl_Constraint_Execute';
        $this->grants[] = 'AblePolecat_AccessControl_Constraint_Open';
        $this->grants[] = 'AblePolecat_AccessControl_Constraint_Read';
        $this->grants[] = 'AblePolecat_AccessControl_Constraint_Write';
      }
      else {
        if (isset($grants['*']['*']['SELECT'])) {
          $this->grants[] = 'AblePolecat_AccessControl_Constraint_Open';
          $this->grants[] = 'AblePolecat_AccessControl_Constraint_Read';
        }
        if (isset($grants['*']['*']['INSERT']) && isset($grants['*']['*']['UPDATE']) && isset($grants['*']['*']['DELETE'])) {
          $this->grants[] = 'AblePolecat_AccessControl_Constraint_Write';
        }
      }
    }
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->grants = array();
  }
}