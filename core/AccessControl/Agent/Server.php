<?php
/**
 * @file: Server.php
 * The server access control agent is the super administrator within Able Polecat.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role', 'Server.php')));

class AblePolecat_AccessControl_Agent_Server extends AblePolecat_AccessControl_AgentAbstract {
  
  /**
   * Constants.
   */
  const UUID = '4d29bf99-beb7-44b1-bd3b-83f5bba31165';
  const NAME = 'Server Agent';
  
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
    
    $Agent = NULL;
    if (is_a($Subject, 'AblePolecat_Mode_Server')) {
      $Agent = new AblePolecat_AccessControl_Agent_Server();
    }
    else {
      $msg = "Access denied to server agent.";
      isset($Subject) ? $msg .= ' ' . get_class($Subject) . ' does not have sufficient privilege.' : NULL;
      throw new AblePolecat_AccessControl_Exception($msg, AblePolecat_Error::ACCESS_DENIED);
    }
    return $Agent;
  }
}
