<?php
/**
 * @file: Application.php
 * Base class for access control for applications using Able Polecat.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Role', 'Application.php')));
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl' . DIRECTORY_SEPARATOR . 'Agent.php');

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
    parent::initialize();
    $Role = AblePolecat_AccessControl_Role_Application::load();
    $this->assignRole($Role);
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
   * Creational function, load agent from storage with no active session.
   *
   * @return object Instance of AblePolecat_AccessControl_Agent_Application.
   */
  public static function load() {
    $Agent = new AblePolecat_AccessControl_Agent_Application();
    return $Agent;
  }
  
  /**
   * Creational function, load agent from storage and resume session.
   *
   * @return object Instance of AblePolecat_AccessControl_Agent_Application.
   */
  public static function wakeup() {
    return self::load();
  }
}
