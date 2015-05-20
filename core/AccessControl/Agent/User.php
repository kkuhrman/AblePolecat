<?php
/**
 * @file      polecat/core/AccessControl/Agent/User.php
 * @brief     Base class for Able Polecat user access control agent.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));

class AblePolecat_AccessControl_Agent_User extends AblePolecat_AccessControl_AgentAbstract {
  
  /**
   * Anonymous user id and name.
   */
  const ANONYMOUS_USER_ID   = '4f5dcf9d-fd9a-11e4-b890-0050569e00a2';
  const ANONYMOUS_USER_NAME = 'Anonymous';
  
  /**
   * @var AblePolecat_AccessControl_Agent_User Instance of singleton.
   */
  private static $User;
  
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
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    try {
      parent::sleep();
      //
      // If user is not anonymous, make sure a user record is registered with database.
      //
      if (self::ANONYMOUS_USER_ID != $this->getId()) {
        //
        // User id will be null if this is first connection.
        //
        $UserId = $this->getId();
        if (!isset($UserId)) {
          $this->setId(AblePolecat_Registry_Entry_Class::generateUUID());
        }
        
        //
        // See if user is already registered.
        //
        $sql = __SQL()->
          select(
            'id', 
            'name')->
          from('user')->
          where(sprintf("`name` = '%s'", $this->getName()));
        $CommandResult = AblePolecat_Command_Database_Query::invoke($this->getDefaultCommandInvoker(), $sql);
        if (!$CommandResult->success()) {
          //
          // User record does not exist, create it.
          //
          $sql = __SQL()->
            insert(
              'id', 
              'name',
              'authority')->
            into('user')->
            values(
              $this->getId(),
              $this->getName(),
              AblePolecat_Host::getRequest()->getHostName()
            );
          $CommandResult = AblePolecat_Command_Database_Query::invoke($this->getDefaultCommandInvoker(), $sql);
          if (!$CommandResult->success()) {
            AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, sprintf("Failed to save user record for %s",$this->getName()));
          }
        }
        
        //
        // Update session record with user id.
        //
        $sessionNumber = AblePolecat_Session::wakeup()->getSessionNumber();
        $sql = __SQL()->
          update('session')->
          set('userId')->
          values($this->getId())->
          where(sprintf("`sessionNumber` = '%s'", $sessionNumber));
        $CommandResult = AblePolecat_Command_Database_Query::invoke($this->getDefaultCommandInvoker(), $sql);
        if (!$CommandResult->success()) {
          AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, sprintf("Failed to set user id %s for session.",$this->getId()));
        }
        
        //
        // Save active roles to database.
        //
        $activeRoles = $this->getActiveRoleIds();
        foreach($activeRoles as $key => $roleId) {
          $Role = $this->getActiveRole($roleId);
          $roleData = $Role->getAccessControlToken()->serialize();
          $sql = __SQL()->
            insert(
              'sessionNumber', 
              'roleId',
              'userId',
              'roleData')->
            into('role')->
            values(
              $sessionNumber,
              $roleId,
              $this->getId(),
              $this->getName(),
              $roleData
            );
          $CommandResult = AblePolecat_Command_Database_Query::invoke($this->getDefaultCommandInvoker(), $sql);
          if (!$CommandResult->success()) {
            AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, sprintf("Failed to save role data[%s] for %s", $roleId, $this->getName()));
          }
        }
      }
    }
    catch (AblePolecat_Exception $Exception) {
    }
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_AccessControl_Agent_User_System Initialized access control service or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$User)) {
      if (!isset($Subject) || !is_a($Subject, 'AblePolecat_SessionInterface')) {
        throw new AblePolecat_AccessControl_Exception('Cannot initialize user agent without a session.');
      }
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
   * Implementation of AblePolecat_AccessControl_AgentInterface.
   ********************************************************************************/
  
  /**
   * Assign given role to agent.
   *
   * @param AblePolecat_AccessControl_RoleInterface $Role Assigned role.
   */
  public function assignActiveRole(AblePolecat_AccessControl_RoleInterface $Role) {
    //
    // If user is anonymous, attempt to use role as basis for user identity.
    //
    if (self::ANONYMOUS_USER_ID == $this->getId()) {
      if (is_a($Role, 'AblePolecat_AccessControl_Role_Client_Database')) {
        //
        // See if user is already registered.
        //
        $this->setName($Role->getAccessControlToken()->getUsername());
        $sql = __SQL()->
          select(
            'id', 
            'name')->
          from('user')->
          where(sprintf("`name` = '%s'", $this->getName()));
        $CommandResult = AblePolecat_Command_Database_Query::invoke($this->getDefaultCommandInvoker(), $sql);
        if ($CommandResult->success()) {
          $Records = $CommandResult->value();
          isset($Records[0]['id']) ? $this->setId($Records[0]['id']) : NULL;
        }
        else {
          $this->setId(NULL);
        }
      }
    }
    parent::assignActiveRole($Role);
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->setId(self::ANONYMOUS_USER_ID);
    $this->setName(self::ANONYMOUS_USER_NAME);
  }
}
