<?php
/**
 * @file: File.php
 * Uses file to store session data.
 */

require_once(ABLE_POLECAT_PATH. DIRECTORY_SEPARATOR . 'Session.php');

class AblePolecat_Session_File extends AblePolecat_SessionAbstract {
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  protected function initialize() {
    session_save_path(AblePolecat_Server_Paths::getFullPath('session'));
    
    //
    // PHP version 5.3.x
    // @todo: Able Polecat does not support PHP version 5.4
    // session_set_save_handler($this, true);
    //
    session_set_save_handler(
      array($this, 'open'), 
      array($this, 'close'), 
      array($this, 'read'),
      array($this, 'write'),
      array($this, 'destroy'),
      array($this, 'gc')
    );
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