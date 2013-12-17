<?php
/**
 * @file: User.php
 * Environment for Able Polecat User Mode.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'AccessControl.php');

class AblePolecat_Environment_User extends AblePolecat_CacheObjectAbstract implements AblePolecat_EnvironmentInterface {
  
  const UUID = '1c385af0-5f4e-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat User Environment';
  
  /**
   * @var AblePolecat_Environment_Server Singleton instance.
   */
  private static $Environment = NULL;
  
  /**
   * User agent.
   */
  private $Agent;
  
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
    return $this->Agent;
  }
  
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name.
   *
   * @return string Common name.
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
   * @return AblePolecat_Environment_User or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Environment)) {
      //
      // Create environment object.
      //
      self::$Environment = new AblePolecat_Environment_User();
      
      //
      // Initialize access control for application environment settings.
      //
      self::$Environment->Agent = AblePolecat_AccessControl::wakeup($Subject)->getAgent(self::$Environment);
    }
    return self::$Environment;
  }
}