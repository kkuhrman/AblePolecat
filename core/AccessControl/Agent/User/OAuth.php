<?php
/**
 * @file: OAuth.php
 * Encapsulates Able Polecat access control agent for user who authenticates with OAuth 2.0.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Agent', 'User.php')));

class  AblePolecat_AccessControl_Agent_User_OAuth extends AblePolecat_AccessControl_Agent_User {
  
  /**
   * Constants.
   */
  const UUID = 'd7d40850-570f-11e3-949a-0800200c9a66';
  const NAME = 'OAuth 2.0 User';
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
  }
  
  /**
   * Return unique, system-wide identifier for agent.
   *
   * @return string Agent identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for agent.
   *
   * @return string Agent name.
   */
  public static function getName() {
    return self::NAME;
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
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    $Agent = new AblePolecat_AccessControl_Agent_User_OAuth();
    return $Agent;
  }
}