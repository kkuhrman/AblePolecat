<?php
/**
 * @file      polecat/core/Session.php
 * @brief     Default class for Able Polecat user sessions.
  *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(ABLE_POLECAT_CORE. DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Session.php')));

//
// @todo: PHP 5.4 support for SessionHandlerInterface
//
interface AblePolecat_SessionInterface extends AblePolecat_AccessControl_SubjectInterface, AblePolecat_CacheObjectInterface {
  
  const SESSION_VAR_TRANSACTIONS      = 'transactions';
  
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
   * @var Array Transaction log.
   */
  private $transactions;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
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
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    !isset($_SESSION[self::SESSION_VAR_TRANSACTIONS]) ? $_SESSION[self::SESSION_VAR_TRANSACTIONS] = array() : NULL;
    $transactionId = $this->popTransaction();
    while (isset($transactionId)) {
      $_SESSION[self::SESSION_VAR_TRANSACTIONS][] = $transactionId;
      $transactionId = $this->popTransaction();
    }
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
      if (isset($Subject) && is_a($Subject, 'AblePolecat_AccessControl_Agent_Administrator') && @session_start()) {
        self::$Session = new AblePolecat_Session();
        self::$Session->CommandInvoker = $Subject->getDefaultCommandInvoker();
        if (isset($_SESSION[self::SESSION_VAR_TRANSACTIONS])) {
          foreach($_SESSION[self::SESSION_VAR_TRANSACTIONS] as $key => $transactionId) {
            self::$Session->pushTransaction($transactionId);
          }
        }
      }
      else {
        $error_msg = sprintf("%s is not permitted to manage sessions.", AblePolecat_DataAbstract::getDataTypeName($Subject));
        throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
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
  
  /**
   * Pushes given transaction on top of stack.
   * 
   * @param string $tansactionId ID of given transaction.
   *
   * @return int Number of transactions on stack.
   */
  public function pushTransaction($transactionId) {
    $this->transactions[] = $transactionId;
    return count($this->transactions);
  }
  
  /**
   * Pops transaction off top of stack.
   * 
   * @return string $tansactionId ID of given transaction.
   */
  public function popTransaction() {
    $transactionId = array_pop($this->transactions);
    return $transactionId;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  protected function initialize() {
    $this->transactions = array();
  }
}