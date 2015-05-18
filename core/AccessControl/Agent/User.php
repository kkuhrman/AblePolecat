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
  const USER_ID   = '4f5dcf9d-fd9a-11e4-b890-0050569e00a2';
  const USER_NAME = 'Anonymous';
  
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
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->setId(self::USER_ID);
    $this->setName(self::USER_NAME);
  }
}
