<?php
/**
 * @file: Pdo.php
 * Uses PDO database to store session state.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Session.php');

class AblePolecat_Session_Pdo extends AblePolecat_SessionAbstract {
  
  /**
   * @var Session id.
   */
  private static $Id;
  
  /**
   * @var Session.
   */
  private static $Session;
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  protected function initialize() {
    
    $Id = self::getId();
    //
    // @todo load from db
    //
  }
  
  /**
   * Get the session id.
   *
   * @return string Session Id.
   */
  public static function getId() {
    
    if (!isset(self::$Id)) {
      isset($_COOKIE['PHPSESSID']) ? self::$Id = $_COOKIE['PHPSESSID'] : self::$Id = NULL;
    }
    return self::$Id;
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
    //
    // @todo stop session destroy data
    //
  }
  
  /**
   * Sets the current session id.
   *
   * @return string Session id for current session or empty string if no current session id exists.
   */
  public function setSessionId($session_id) {
    //
    // @todo switch sessions, load given id
    //
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // @todo save state to db
    //
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Session or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Session)) {
      self::$Session = new AblePolecat_Session_Pdo();
    }
    return self::$Session;
  }
}