<?php
/**
 * @file: User.php
 * Environment for Able Polecat User Mode.
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');

class AblePolecat_Environment_User extends AblePolecat_CacheObjectAbstract implements AblePolecat_EnvironmentInterface {
  
  /**
   * @var AblePolecat_Environment_Server Singleton instance.
   */
  private static $Environment = NULL;
  
  /**
   * Extends __construct(). 
   * 
   * Sub-classes can override to initialize members prior to load.
   */
  protected function initialize() {
  }
  
  /**
   * Return access control agent.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent() {
    return AblePolecat_AccessControl::wakeup()->getAgent($this);
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
   * @return AblePolecat_Environment_User or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    $Environment = self::$Environment;
    
    if (!isset($Environment)) {
      //
      // Create environment object.
      //
      $Environment = new AblePolecat_Environment_User();
      
      //
      // Initialize access control for application environment settings.
      //
      $Agent = $Environment->getAgent();
      
      //
      // Initialize singleton instance.
      //
      self::$Environment = $Environment;
    }
    return self::$Environment;
  }
}