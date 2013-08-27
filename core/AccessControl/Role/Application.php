<?php
/**
 * @file: Application.php
 * Default access control role assigned to web applications.
 */
 
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');

class AblePolecat_AccessControl_Role_Application implements AblePolecat_AccessControl_RoleInterface {
  
  /**
   * Constants.
   */
  const UUID = 'de09d5b0-60c0-11e2-bcfd-0800200c9a66';
  const NAME = 'Application';
  
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
      case 'AblePolecat_AccessControl_Agent_Application':
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
    $Role = new AblePolecat_AccessControl_Role_Application();
    return $Role;
  }
}
