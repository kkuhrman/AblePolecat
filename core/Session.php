<?php
/**
 * @file: Session.php
 * Default class for Able Polecat user sessions.
 */

require_once(ABLE_POLECAT_PATH. DIRECTORY_SEPARATOR . 'CacheObject.php');

//
// @todo: PHP 5.4 support for SessionHandlerInterface
//
interface AblePolecat_SessionInterface extends AblePolecat_AccessControl_SubjectInterface, AblePolecat_CacheObjectInterface {
}

abstract class AblePolecat_SessionAbstract implements AblePolecat_SessionInterface {
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  abstract protected function initialize();
  
  /**
   * Returns the current session status.
   *
   * @return int PHP_SESSION_DISABLED | PHP_SESSION_NONE | PHP_SESSION_ACTIVE.
   */
  public function getSessionStatus() {
    //
    // PHP 5.4+
    //
    // $session_status = session_status();
    $session_status = NULL;
    // $status = array(PHP_SESSION_DISABLED => 'disabled', PHP_SESSION_NONE => 'none', PHP_SESSION_ACTIVE => 'active');
    // isset($status[$session_status]) ? $status_str = $status[$session_status] : $status_str = 'unknown';
    return $session_status;
  }
	
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}

/**
 * Standard session wrapper.
 */
class AblePolecat_Session extends AblePolecat_SessionAbstract {
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  protected function initialize() {
  }
  
  /**
   * Ideally unique id will be UUID.
   *
   * @return string Subject unique identifier.
   */
  public static function getId() {
    $session_id = session_id();
    return $session_id;
  }
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public static function getName() {
    return 'Able Polecat Session';
  }
  
  /**
   * Destroy a session.
   *
   * @param string $session_id ID of session to destroy.
   * 
   * @return bool TRUE on success, otherwise FALSE.
   */
  public function destroy($session_id) {
    $ret = FALSE;
    if (self::getId() == $session_id) {
      $ret = session_destroy();
    }
    return $ret;
  }
  
  /**
   * Sets the current session id.
   *
   * @return string Session id for current session or empty string if no current session id exists.
   */
  public function setSessionId($session_id) {
    return session_id($session_id);
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    session_write_close();
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Session or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    $Session = NULL;
    if (@session_start()) {
      $Session = new AblePolecat_Session();
    }
    return $Session;
  }
}

/**
  * Exceptions thrown by AblePolecat_Session and sub-classes.
  */
class AblePolecat_Session_Exception extends AblePolecat_Exception {}
