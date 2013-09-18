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
  protected function initialize() {
    //
    // @todo: PHP version 5.4
    //
    session_save_path(AblePolecat_Server_Paths::getFullPath('session'));
    session_set_save_handler($this, true);
    session_start();
  }
  
  /**
   * Close the session.
   *
   * @return bool TRUE on success, otherwise FALSE.
   */
  public function close() {
    return TRUE;
  }
  
  /**
   * Destroy a session.
   *
   * @param string $session_id ID of session to destroy.
   * 
   * @return bool TRUE on success, otherwise FALSE.
   */
  public function destroy($session_id) {
    return TRUE;
  }
  
  /**
   * Cleanup old sessions.
   *
   * @param string $maxlifetime Maximum life of sessions which have not been updated.
   *
   * @return bool TRUE on success, otherwise FALSE.
   */
  public function gc($maxlifetime) {
    return TRUE;
  }
  
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
   * Initialize the session.
   *
   * @param string $save_path The path where to store/retrieve the session.
   * @param string $name The session name.
   *
   * @return bool TRUE on success, otherwise FALSE.
   */
  public function open($save_path, $name) {
    return TRUE;
  }
  
  /**
   * Read session data.
   *
   * @param string $session_id The session ID.
   *
   * @return string Encoded session data.
   */
  public function read($session_id) {
    return '';
  }
  
  /**
   * Write session data to storage.
   *
   * @param string $session_id The session ID.
   * @param string $session_data The encoded session data.
   *
   * @return bool TRUE on success otherwise FALSE.
   */
  public function write($session_id , $session_data) {
    //
    // @todo: PHP version 5.4
    //
    return TRUE;
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
