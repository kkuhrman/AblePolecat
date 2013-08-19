<?php
/**
 * @file: Session.php
 * Default class for Able Polecat user sessions.
 */
 
class AblePolecat_Session implements Serializable {
  
  /**
   * Write session data and end session.
   *
   * @return void.
   */
  public function commit() {
    session_write_close();
  }
  
  /**
   * Destroys all data registered to a session.
   *
   * @return bool.
   */
  public function destroy() {
    return session_destroy();
  }
  
  /**
   * Get the current session id.
   *
   * @return string Session id for current session or empty string if no current session id exists.
   */
  public function getSessionId() {
    $session_id = session_id();
    return $session_id;
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
   * Sets the current session id.
   *
   * @return string Session id for current session or empty string if no current session id exists.
   */
  public function setSessionId($session_id) {
    return session_id($session_id);
  }
  
  /**
   * Start new or resume existing session.
   *
   * @return bool TRUE if a session was successfully started, otherwise FALSE.
   */
  public static function start() {
    
    $session = NULL;
    if (@session_start()) {
      $session = new AblePolecat_Session();
    }
    return $session;
  }
  
  /**
   * Encodes the current session data as a session encoded string.
   */
  public function serialize() {
    $session_data = session_encode();
    return serialize($session_data);
  }
  
  /**
   * Decodes session data from a session encoded string.
   */
  public function unserialize($data) {
    if ($this->getSessionStatus() === PHP_SESSION_ACTIVE) {
      $session_data = unserialize($data);
      if (!session_decode($session_data)) {
        throw new AblePolecat_Session_Exception(
          'Failed to decode session data.',
          AblePolecat_Session_Exception::ERROR_SESSION_DECODE_FAIL
      );
      }
    }
    else {
      throw new AblePolecat_Session_Exception(
        'Cannot decode unavailable session data.',
        AblePolecat_Session_Exception::ERROR_SESSION_NOT_STARTED
      );
    }
  }
  
  final protected function __construct() {
  }
}

/**
  * Exceptions thrown by AblePolecat_Session and sub-classes.
  */
class AblePolecat_Session_Exception extends AblePolecat_Exception {}
