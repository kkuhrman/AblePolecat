<?php
/**
 * @file      polecat/core/AccessControl/Agent/User.php
 * @brief     Base class for Able Polecat user access control agent.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));

class AblePolecat_AccessControl_Agent_User extends AblePolecat_AccessControl_AgentAbstract {
  
  /**
   * @var AblePolecat_AccessControl_Agent_User Instance of singleton.
   */
  private static $User;
  
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
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$User)) {
      $Args = func_get_args();
      isset($Args[0]) ? $Subject = $Args[0] : $Subject = NULL;
      isset($Args[1]) ? $Mode = $Args[1] : $Mode = NULL;
      if (isset($Subject) && is_a($Subject, 'AblePolecat_AccessControl_Agent_System')) {
        self::$User = new AblePolecat_AccessControl_Agent_User($Mode);
      }
      else {
        $error_msg = sprintf("%s is not permitted to wakeup user access control agent.", AblePolecat_Data::getDataTypeName($Subject));
        throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
      }
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
    $this->userId = 0; // default = anonymous
    $this->userName = 'anonymous';
  }
}
