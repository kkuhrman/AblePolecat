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
 * @version   0.7.2
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
   * @return int Internal (Able Polecat) session ID.
   */
  public static function getSessionNumber();
  
  /**
   * Returns the current session status.
   *
   * @return int PHP_SESSION_DISABLED | PHP_SESSION_NONE | PHP_SESSION_ACTIVE.
   */
  public function getSessionStatus();
  
  /**
   * Retrieve variable from $_SESSION global variable.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Class attempting to retrieve variable.
   * @param string $variableName Name of session variable.
   *
   * @return mixed $variableValue Value of session variable or NULL.
   */
  public static function getSessionVariable(AblePolecat_AccessControl_SubjectInterface $Subject, $variableName);
  
  /**
   * Sets the current session id.
   *
   * @return string Session id for current session or empty string if no current session id exists.
   */
  public function setSessionId($session_id);
  
  /**
   * Save variable to $_SESSION global variable.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Class attempting to set variable.
   * @param string $variableName Name of session variable.
   * @param mixed $variableValue Value of session variable.
   */
  public static function setSessionVariable(AblePolecat_AccessControl_SubjectInterface $Subject, $variableName, $variableValue);
}

/**
 * Standard session wrapper.
 */
class AblePolecat_Session extends AblePolecat_AccessControl_SubjectAbstract implements AblePolecat_SessionInterface {
  
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
  
  /**
   * @var int Internal (Able Polecat) session ID.
   */
  private $sessionNumber;
    
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Scope of operation.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SESSION';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * Ideally unique id will be UUID.
   *
   * @return string PHP session ID.
   * @throw AblePolecat_Session_Exception if session appears to have been tampered with.
   */
  public function getId() {
    
    $sessionId = NULL;
    
    if (isset(self::$Session)) {
      $sessionId = session_id();
      if (self::$Session->sessionId !== $sessionId) {
        $sessionId = NULL;
        throw new AblePolecat_Session_Exception("Session ID has been changed.");
      }
    }
    else {
      throw new AblePolecat_Session_Exception("Session has not been initiated.");
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
    try {
      parent::sleep();
      
      //
      // Remove any unauthorized session settings.
      //
      foreach($_SESSION as $varName => $varValue) {
        unset($_SESSION[$varName]);
      }
      foreach($this->sessionGlobal as $varName => $varValue) {
        $_SESSION[$varName] = $varValue;
      }
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Session state: ' . serialize($_SESSION));
      
      //
      // Close and save PHP session.
      //
      session_write_close();
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Session closed.');
    }
    catch (AblePolecat_Exception $Exception) {
    }
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
      self::$Session = new AblePolecat_Session();
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
   * @return int Internal (Able Polecat) session ID.
   */
  public static function getSessionNumber() {
    
    $sessionNumber = 0;
    
    if (isset(self::$Session)) {
      $sessionNumber = self::$Session->sessionNumber;
    }
    return $sessionNumber;
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
   * Retrieve variable from $_SESSION global variable.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Class attempting to retrieve variable.
   * @param string $variableName Name of session variable.
   *
   * @return mixed $variableValue Value of session variable or NULL.
   */
  public static function getSessionVariable(AblePolecat_AccessControl_SubjectInterface $Subject, $variableName) {
    
    $value = NULL;
    
    if (isset(self::$Session) &&
        isset(self::$Session->sessionGlobal) && 
        is_array(self::$Session->sessionGlobal) && 
        isset(self::$Session->sessionGlobal[$variableName])) {
      $className = get_class($Subject);
      switch ($className) {
        default:
          break;
        case 'AblePolecat_Transaction_Restricted_Install':
          $value = self::$Session->sessionGlobal[$className][$variableName];
          break;
      }
    }
    return $value;
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
   * Save variable to $_SESSION global variable.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Class attempting to set variable.
   * @param string $variableName Name of session variable.
   * @param mixed $variableValue Value of session variable.
   */
  public static function setSessionVariable(AblePolecat_AccessControl_SubjectInterface $Subject, $variableName, $variableValue) {
    if (isset(self::$Session) &&
        isset(self::$Session->sessionGlobal) && 
        is_array(self::$Session->sessionGlobal) && 
        is_scalar($variableName)) {
      $className = get_class($Subject);
      switch ($className) {
        default:
          break;
        case 'AblePolecat_Transaction_Restricted_Install':
          !isset(self::$Session->sessionGlobal[$className]) ? self::$Session->sessionGlobal[$className] = array() : NULL;
          switch ($variableName) {
            default:
              break;
            case AblePolecat_Host::POLECAT_INSTALL_TRX:
            case AblePolecat_Host::POLECAT_INSTALL_SAVEPT:
            case AblePolecat_Host::POLECAT_INSTALL_DBNAME:
            case AblePolecat_Host::POLECAT_INSTALL_DBUSER:
            case AblePolecat_Host::POLECAT_INSTALL_DBPASS:
              self::$Session->sessionGlobal[$className][$variableName] = $variableValue;
              break;
          }
          break;
      }
    }
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
    $this->setId($this->sessionId);
  
    //
    // Cache session global variable to ensure that it is not used/tampered with by
    // application/user mode.
    //
    $this->sessionGlobal = $_SESSION;
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Session state: ' . serialize($_SESSION));
    
    //
    // Use internal session number for RDBMS.
    //
    if (AblePolecat_Mode_Config::coreDatabaseIsReady()) {
      $sql = __SQL()->
        select(
          'sessionNumber')->
        from('session')->
        where(sprintf("`phpSessionId` = '%s'", $this->sessionId));
      $CommandResult = AblePolecat_Command_Database_Query::invoke($this->getDefaultCommandInvoker(), $sql);
      if ($CommandResult->success() && count($CommandResult->value())) {
        $Records = $CommandResult->value();
        isset($Records[0]['sessionNumber']) ? $this->sessionNumber = $Records[0]['sessionNumber'] : NULL;
      }
      else {
        isset($_SERVER['REMOTE_ADDR']) ? $remoteAddress = $_SERVER['REMOTE_ADDR'] : $remoteAddress = 'UNKNOWN';
        $sql = __SQL()->
          insert(
            'phpSessionId', 
            'hostName',
            'remoteAddress')->
          into('session')->
          values(
            $this->sessionId, 
            AblePolecat_Host::getRequest()->getHostName(),
            $remoteAddress
          );
        $CommandResult = AblePolecat_Command_Database_Query::invoke($this->getDefaultCommandInvoker(), $sql);
        if ($CommandResult->success() && count($CommandResult->value())) {
          $Records = $CommandResult->value();
          isset($Records['lastInsertId']) ? $this->sessionNumber = $Records['lastInsertId'] : NULL;
        }
      }
    }
    else {
      $this->sessionNumber = 0;
    }
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, sprintf("Session number is %d.", $this->sessionNumber));
  }
}