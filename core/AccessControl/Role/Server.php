<?php
/**
 * @file      polecat/core/AccessControl/Role/User.php
 * @brief     Agent role for system/server.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));

class AblePolecat_AccessControl_Role_Server extends AblePolecat_AccessControl_RoleAbstract {
  
  /**
   * Constants.
   */
  const UUID = 'cf753b72-5ca1-41a3-9a7c-bc1209b39444';
  const NAME = 'Server Role';
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
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
   * @return AblePolecat_CacheObjectInterface or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    $Role = new AblePolecat_AccessControl_Role_Server();
    return $Role;
  }
}
