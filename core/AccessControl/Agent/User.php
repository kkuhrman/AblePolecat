<?php
/**
 * @file      polecat/core/AccessControl/Agent/User.php
 * @brief     Base class for Able Polecat user access control agent.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));

class AblePolecat_AccessControl_Agent_User extends AblePolecat_AccessControl_AgentAbstract {
  
  /**
   * @var AblePolecat_AccessControl_Agent_User Instance of singleton.
   */
  private static $User;
  
  /**
   * @var Array[AblePolecat_AccessControl_RoleInterface].
   */
  private $ActiveRoles;
  
  /**
   * @var int User id on localhost.
   */
  private $userId;
  
  /**
   * @var string User name on localhost.
   */
  private $userName;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'USER';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * System unique ID.
   *
   * @return scalar Subject unique identifier.
   */
  public function getId() {
    return $this->userId;
  }
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public function getName() {
    return $this->userName;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
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
   * @return AblePolecat_AccessControl_Agent_System Initialized access control service or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$User)) {
      //
      // Intentionally do not pass $Subject to constructor as this would save
      // it as default command invoker. Agents invoke their own commands.
      // @see AblePolecat_AccessControl_AgentAbstract::initialize()
      //
      self::$User = new AblePolecat_AccessControl_Agent_User();
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'User agent initialized.');
    }
    return self::$User;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Return active role by id.
   *
   * @param string $roleId The ID of the active role, if assigned.
   *
   * @return mixed Instance of AblePolecat_AccessControl_RoleInterface, otherwise FALSE.
   */
  public function getActiveRole($roleId) {
    
    $Role = FALSE;
    if (isset($roleId) && is_scalar($roleId) && isset($this->ActiveRoles[$roleId])) {
      $Role = $this->ActiveRoles[$roleId];
    }
    return $Role;
  }
  
  /**
   * Returns a list of IDs of all assigned, active roles.
   *
   * @return Array.
   */
  public function getActiveRoleIds() {
    return array_keys($this->ActiveRoles);
  }
  
  /**
   * Load agent roles active for for current session.
   *
   * @return Array.
   */
  public function refreshActiveRoles() {
    
    $ClassRegistry = AblePolecat_Registry_Class::wakeup();
    $this->ActiveRoles = array();
    
    //
    // Get active roles from the database.
    //
    $sql = __SQL()->
      select('sessionNumber', 'roleId', 'userId', 'roleData')->
      from('role')->
      where(sprintf("sessionNumber = '%s'", AblePolecat_Host::getSessionNumber()));
    $CommandResult = AblePolecat_Command_Database_Query::invoke($this, $sql);
    if ($CommandResult->success()) {
      $results = $CommandResult->value();
      try {
        foreach($results as $key => $role) {
          //
          // assign roles to agent
          //
          $roleClassName = $role['roleId'];
          $Role = $ClassRegistry->loadClass($roleClassName);
          if (isset($Role)) {
            $this->assignActiveRole($Role);
          }
          else {
            //
            // @todo: complain
            //
            AblePolecat_Command_Log::invoke($this, "Failed to load user role $roleClassName.", AblePolecat_LogInterface::WARNING);
          }
        }
      }
      catch (AblePolecat_Exception $Exception) {
        AblePolecat_Command_Log::invoke($this, $Exception->getMessage(), AblePolecat_LogInterface::WARNING);
      }
    }
    if (0 === count($this->ActiveRoles)) {
      //
      // No roles assigned, assume anonymous user.
      //
      $Role = $ClassRegistry->loadClass('AblePolecat_AccessControl_Role_User_Anonymous');
      $this->assignActiveRole($Role);
    }
    
    return $this->ActiveRoles;
  }
  
  /**
   * Assign given role to agent on authority of subject.
   *
   * @param AblePolecat_AccessControl_RoleInterface $Role Assigned role.
   * @param $authorityId ID of authority granting role.
   */
  protected function assignActiveRole( 
    AblePolecat_AccessControl_RoleInterface $Role,
    $authorityId = NULL
  ) {
    
    if (!isset($this->ActiveRoles[$Role->getId()])) {
      $this->ActiveRoles[$Role->getId()] = $Role;
    }
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->ActiveRoles = array();
    $this->userId = 0; // default = anonymous
    $this->userName = 'anonymous';
  }
}
