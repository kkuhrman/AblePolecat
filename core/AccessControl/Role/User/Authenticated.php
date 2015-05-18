<?php
/**
 * @file  AblePolecat/core/AccessControl/Role/User/Authenticated.php
 * @brief Role reserved for authenticated agent (user).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role', 'User.php')));

interface AblePolecat_AccessControl_Role_User_AuthenticatedInterface extends AblePolecat_AccessControl_Role_UserInterface {
  /**
   * @return string ID of authenticating authority.
   */
  public function getAuthority();
}

class AblePolecat_AccessControl_Role_User_Authenticated extends AblePolecat_AccessControl_RoleAbstract implements AblePolecat_AccessControl_Role_User_AuthenticatedInterface {
  
  /**
   * @var int Role id on localhost.
   */
  private $roleId;
  
  /**
   * @var string Role name on localhost.
   */
  private $roleName;
  
  /**
   * @var string ID of authenticating authority.
   */
  private $authorityId;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * System unique ID.
   *
   * @return scalar Subject unique identifier.
   */
  public function getId() {
    return $this->roleId;
  }
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public function getName() {
    return $this->roleName;
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
    
    $Role = new AblePolecat_AccessControl_Role_User_Authenticated($Subject);
    $args = func_get_args();
    isset($args[1]) ? $Role->authorityId = $args[1] : NULL;
    return $Role;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Role_User_AuthenticatedInterface.
   ********************************************************************************/
  
  /**
   * @var string ID of authenticating authority.
   */
  public function getAuthority() {
    return $this->authorityId;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->authorityId = NULL;
    $this->roleId = 0;
    $this->roleName = 'not authenticated';
  }
}