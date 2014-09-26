<?php
/**
 * @file: Authenticated.php
 * Role reserved for anonymous agent (user).
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role', 'User.php')));

interface AblePolecat_AccessControl_Role_User_AuthenticatedInterface extends AblePolecat_AccessControl_Role_UserInterface {
  /**
   * @return string ID of authenticating authority.
   */
  public function getAuthority();
}

class AblePolecat_AccessControl_Role_User_Authenticated extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_Role_User_AuthenticatedInterface {
  
  /**
   * Constants.
   */
  const UUID = '156cb28a-44f8-11e4-b353-0050569e00a2';
  const NAME = 'Authenticated User';
  
  /**
   * @var string ID of authenticating authority.
   */
  private $authorityId;
  
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
  }
}