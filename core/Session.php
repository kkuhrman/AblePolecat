<?php
/**
 * @file      polecat/core/Session.php
 * @brief     Default class for Able Polecat user sessions.
 *
 * The main purpose of the Able Polecat session object is to prevent tampering with the global
 * session variable and session ID by application and user modes.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(ABLE_POLECAT_CORE. DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Session.php')));

//
// @todo: PHP 5.4 support for SessionHandlerInterface
//
interface AblePolecat_SessionInterface extends AblePolecat_AccessControl_SubjectInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * Destroy a session.
   *
   * @param string $session_id ID of session to destroy.
   * 
   * @return bool TRUE on success, otherwise FALSE.
   */
  public function destroy($session_id);
  
  /**
   * Returns the current session status.
   *
   * @return int PHP_SESSION_DISABLED | PHP_SESSION_NONE | PHP_SESSION_ACTIVE.
   */
  public function getSessionStatus();
  
  /**
   * Sets the current session id.
   *
   * @return string Session id for current session or empty string if no current session id exists.
   */
  public function setSessionId($session_id);
}

/**
 * Standard session wrapper.
 */
class AblePolecat_Session extends AblePolecat_CacheObjectAbstract implements AblePolecat_SessionInterface {
  
  /**
   * @var AblePolecat_Session Instance of singleton.
   */
  private static $Session;
  
  /**
   * @var string PHP session ID.
   */
  private $sessionId;
  
  /**
   * @var Array Initial PHP session state.
   */
  private $sessionGlobal;
    
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Ideally unique id will be UUID.
   *
   * @return string PHP session ID.
   * @throw AblePolecat_Session if session appears to have been tampered with.
   */
  public function getId() {
    
    $sessionId = NULL;
    
    if (isset(self::$Session)) {
      $sessionId = session_id();
      if (self::$Session->sessionId !== $sessionId) {
        $sessionId = NULL;
        throw new AblePolecat_Session("Session ID has been changed.");
      }
    }
    else {
      throw new AblePolecat_Session("Session has not been initiated.");
    }
    return $sessionId;
  }
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public function getName() {
    return 'session';
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
    if (!isset(self::$Session)) {
      $args = func_get_args();
      $DefaultCommandInvoker = NULL;
      if(isset($args[1]) && is_a($args[1], 'AblePolecat_HostInterface')) {
        $DefaultCommandInvoker = $args[1];
        self::$Session = new AblePolecat_Session($DefaultCommandInvoker);
      }
      if (!isset(self::$Session)) {
        $error_msg = sprintf("%s is not permitted to manage sessions.", AblePolecat_Data::getDataTypeName($Subject));
        throw new AblePolecat_Session($error_msg, AblePolecat_Error::ACCESS_DENIED);
      }
    }
    return self::$Session;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_SessionInterface.
   ********************************************************************************/
  
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
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  protected function initialize() {
    //
    // Start or resume session.
    //
    session_start();
    $this->sessionId = session_id();
  
    //
    // Cache session global variable to ensure that it is not used/tampered with by
    // application/user mode.
    //
    $this->sessionGlobal = $_SESSION;
  }
}