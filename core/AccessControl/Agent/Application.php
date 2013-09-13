<?php
/**
 * @file: Application.php
 * Base class for access control for applications using Able Polecat.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Agent.php')));

class AblePolecat_AccessControl_Agent_Application extends AblePolecat_AccessControl_AgentAbstract {
  
  /**
   * Constants.
   */
  const UUID = '6c1c36d0-60bd-11e2-bcfd-0800200c9a66';
  const NAME = 'Application';
  
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
    $Agent = new AblePolecat_AccessControl_Agent_Application();
    return $Agent;
  }
}
