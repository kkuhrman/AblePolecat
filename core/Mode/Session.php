<?php
/**
 * @file      polecat/core/Mode/Session.php
 * @brief     Base class for Session mode (password, security token protected etc).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'User.php')));

class AblePolecat_Mode_Session extends AblePolecat_ModeAbstract {
  
  /**
   * Constants.
   */
  const UUID = 'bbea2770-39bb-11e4-916c-0800200c9a66';
  const NAME = 'AblePolecat_Mode_Session';
  
  /**
   * @var Instance of Singleton.
   */
  private static $SessionMode;
  
  /**
   * @var Instance of AblePolecat_SessionInterface.
   */
  private $Session;
  
  /**
   * @var Array Initial PHP session state.
   */
  private $sessionGlobal;
  
  /**
   * @var string PHP session ID.
   */
  private $sessionId;
  
  /**
   * @var int Internal (Able Polecat) session ID.
   */
  private $sessionNumber;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $Environment;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_SubjectInterface.
   ********************************************************************************/  
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
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
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Session or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$SessionMode)) {
      //
      // Create instance of session mode
      //
      self::$SessionMode = new AblePolecat_Mode_Session();
    }
      
    return self::$SessionMode;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Command_TargetInterface.
   ********************************************************************************/
   
  /**
   * Execute a command or pass back/forward chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public function execute(AblePolecat_CommandInterface $Command) {
    
    $Result = NULL;
    
    //
    // @todo: check invoker access rights
    //
    switch ($Command::getId()) {
      default:
        //
        // Not handled
        //
        break;
    }
    //
    // Pass command to next link in chain of responsibility
    //
    $Result = $this->delegateCommand($Command, $Result);
    return $Result;
  }
  
  /********************************************************************************
   * Callback functions used by session_set_save_handler().
   ********************************************************************************/
  
  /**
   * First callback function executed when PHP session is started.
   *
   * @param string $savePath
   * @param string $sessionName
   *
   * @return bool
   */
  public static function openSession($savePath, $sessionName) {
    return TRUE;
  }
  
  /**
   * Callback invoked when session_write_close() is called.
   */
  public static function closeSession() {
    return TRUE;
  }
  
  /**
   * @param string $sessionId
   *
   * @return Session encoded (serialized) string, or an empty string no data to read. 
   */
  public static function readSession($sessionId) {
    return '';
  }
  
  /**
   * Called when the session needs to be saved and closed. 
   *
   * @param string $sessionId
   * @param string $data
   */
  public static function writeSession($sessionId, $data) {
    return TRUE;
  }
  
  /**
   * Executed when a session is destroyed.
   *
   * @param string $sessionId
   * 
   * @return bool
   */
  public static function destroySession($sessionId) {
    return TRUE;
  }
  
  /**
   * The garbage collector callback is invoked internally by PHP periodically 
   * in order to purge old session data. 
   *
   * @param int $lifetime
   *
   * @return bool
   */
  public static function collectSessionGarbage($lifetime) {
    return TRUE;
  }
  
  /**
   * Executed when a new session ID is required. 
   *
   * @return string Valid Able Polecat session id.
   */
  public static function createSessionId() {
    return uniqid();
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Override PHP defaults for session handling.
   */
  private function initializeSessionSecurity() {
    
    //
    // Session ID cookie is deleted immediately when browser is terminated.
    //
    ini_set('session.cookie_lifetime', 0);
    
    //
    // Use only cookies for session ID management.
    //
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    
    //
    // Reject user supplied session id.
    //
    ini_set('session.use_strict_mode', 1);
    
    //
    // Disallow access to session cookie by JavaScript.
    //
    ini_set('session.cookie_httponly', 1);
    
    //
    // Disabling transparent session ID management improves general 
    // session ID security by removing possibility of session ID 
    // injection and session ID leak. 
    //
    ini_set(' session.use_trans_sid', 0);
    
    //
    // Stronger hash function will generates stronger session ID.
    //
    ini_set('session.hash_function', 'sha256');
    
    //
    // Session handler callback functions.
    //
    session_set_save_handler(
      array('AblePolecat_Mode_Session', 'openSession'),
      array('AblePolecat_Mode_Session', 'closeSession'),
      array('AblePolecat_Mode_Session', 'readSession'),
      array('AblePolecat_Mode_Session', 'writeSession'),
      array('AblePolecat_Mode_Session', 'destroySession'),
      array('AblePolecat_Mode_Session', 'collectSessionGarbage')
    );
  }
  
  /**
   * @return AblePolecat_Message_RequestInterface
   */
  public static function getSessionId() {
    
    $sessionId = NULL;
    
    if (isset(self::$SessionMode)) {
      $sessionId = self::$SessionMode->sessionId;
    }
    return $sessionId;
  }
  
  /**
   * @return int Internal (Able Polecat) session ID.
   */
  public static function getSessionNumber() {
    
    $sessionNumber = 0;
    
    if (isset(self::$SessionMode)) {
      $sessionNumber = self::$SessionMode->sessionNumber;
    }
    return $sessionNumber;
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
    
    if (isset(self::$SessionMode) &&
        isset(self::$SessionMode->sessionGlobal) && 
        is_array(self::$SessionMode->sessionGlobal) && 
        isset(self::$SessionMode->sessionGlobal[$variableName])) {
      $className = get_class($Subject);
      switch ($className) {
        default:
          break;
        case 'AblePolecat_Transaction_Restricted_Install':
          $value = self::$SessionMode->sessionGlobal[$className][$variableName];
          break;
      }
    }
    return $value;
  }
  
  /**
   * Save variable to $_SESSION global variable.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Class attempting to set variable.
   * @param string $variableName Name of session variable.
   * @param mixed $variableValue Value of session variable.
   */
  public static function setSessionVariable(AblePolecat_AccessControl_SubjectInterface $Subject, $variableName, $variableValue) {
    if (isset(self::$SessionMode) &&
        isset(self::$SessionMode->sessionGlobal) && 
        is_array(self::$SessionMode->sessionGlobal) && 
        is_scalar($variableName)) {
      $className = get_class($Subject);
      switch ($className) {
        default:
          break;
        case 'AblePolecat_Transaction_Restricted_Install':
          !isset(self::$SessionMode->sessionGlobal[$className]) ? self::$SessionMode->sessionGlobal[$className] = array() : NULL;
          switch ($variableName) {
            default:
              break;
            case self::POLECAT_INSTALL_TRX:
            case self::POLECAT_INSTALL_SAVEPT:
            case self::POLECAT_INSTALL_DBNAME:
            case self::POLECAT_INSTALL_DBUSER:
            case self::POLECAT_INSTALL_DBPASS:
              self::$SessionMode->sessionGlobal[$className][$variableName] = $variableValue;
              break;
          }
          break;
      }
    }
  }
  
  /**
   * Validates given command target as a forward or reverse COR link.
   *
   * @param AblePolecat_Command_TargetInterface $Target.
   * @param string $direction 'forward' | 'reverse'
   *
   * @return bool TRUE if proposed COR link is acceptable, otherwise FALSE.
   */
  protected function validateCommandLink(AblePolecat_Command_TargetInterface $Target, $direction) {
    
    $ValidLink = FALSE;
    
    switch ($direction) {
      default:
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_FWD:
        $ValidLink = is_a($Target, 'AblePolecat_Mode_User');
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        $ValidLink = is_a($Target, 'AblePolecat_Host');
        break;
    }
    return $ValidLink;
  }
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // Wakeup host and establish as reverse command target.
    //
    $CommandChain = AblePolecat_Command_Chain::wakeup();
    $Host = AblePolecat_Host::wakeup();
    $CommandChain->setCommandLink($Host, $this);
    
    //
    // Start or resume session.
    // Able Polecat does not use PHP session. But it does check the session global variable
    // to ensure that it is not tampered with by extension classes.
    //
    $this->initializeSessionSecurity();
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Session security initialized.');
    session_start();
    
    //
    // Cache session global variable to ensure that it is not used/tampered with by
    // application/user mode.
    //
    $this->sessionGlobal = $_SESSION;
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Session state: ' . serialize($_SESSION));
    
    //
    // PHP session id
    //
    $this->sessionId = session_id();
    
    //
    // Use internal session number for RDBMS.
    //
    if (AblePolecat_Mode_Server::getActiveCoreDatabaseName()) {
      $sql = __SQL()->
        select(
          'sessionNumber')->
        from('session')->
        where(sprintf("`phpSessionId` = '%s'", $this->sessionId));
      $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), $sql);
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
        $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), $sql);
        if ($CommandResult->success() && count($CommandResult->value())) {
          $Records = $CommandResult->value();
          isset($Records['lastInsertId']) ? $this->sessionNumber = $Records['lastInsertId'] : NULL;
        }
      }
    }
    else {
      $this->sessionNumber = 0;
    }
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, sprintf("Session number is %d.", self::getSessionNumber()));
    
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Session mode is initialized.');
  }
}