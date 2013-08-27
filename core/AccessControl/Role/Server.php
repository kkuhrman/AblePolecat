<?php
/**
 * @file: Server.php
 * Default access control role assigned to web applications.
 */
 
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');

class AblePolecat_AccessControl_Role_Server implements AblePolecat_AccessControl_RoleInterface {
  
  /**
   * Constants.
   */
  const UUID = 'cf753b72-5ca1-41a3-9a7c-bc1209b39444';
  const NAME = 'Server Role';
  
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
   * Specify if role is authorized for given agent object.
   *
   * @param object Object implementing AblePolecat_AccessControl_AgentInterface.
   *
   * @return bool TRUE if role is authorized for given agent object, otherwise FALSE.
   */
  public static function isAuthorized(AblePolecat_AccessControl_AgentInterface $Agent) {
    
    $authorized = FALSE;
    
    switch (get_class($Agent)) {
      case 'AblePolecat_AccessControl_Agent_Server':
        $authorized = TRUE;
        break;
      default:
        break;
    }
    return $authorized;
  }
  
  /**
   * Creational function, initialize members from storage.
   *
   * @return object Instance of class which implments AblePolecat_AccessControl_RoleInterface.
   */
  public static function load() {
    $Role = new AblePolecat_AccessControl_Role_Server();
    return $Role;
  }
}
