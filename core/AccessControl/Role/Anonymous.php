<?php
/**
 * @file: Anonymous.php
 * Role reserved for anonymous agent (user).
 */
 
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl' . DIRECTORY_SEPARATOR . 'Role.php');

class AblePolecat_AccessControl_Role_Anonymous extends AblePolecat_AccessControl_RoleAbstract {
  
  /**
   * Constants.
   */
  const UUID = '5c14a350-6976-11e2-bcfd-0800200c9a66';
  const NAME = 'Anonymous';
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
  
  /**
   * Return unique, system-wide identifier for agent.
   *
   * @return string Role identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for role.
   *
   * @return string Role name.
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
   * @return AblePolecat_CacheObjectInterface or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    $Role = new AblePolecat_AccessControl_Role_Anonymous();
    return $Role;
  }
}
