<?php
/**
 * @file      polecat/core/Mode/Session.php
 * @brief     Base class for Session mode (password, security token protected etc).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Session.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode.php')));

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
    try {
      parent::sleep();
    }
    catch (AblePolecat_Exception $Exception) {
    }
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
   * Implementation of AblePolecat_ModeInterface.
   ********************************************************************************/
  
  /**
   * Returns assigned value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   *
   * @return mixed Assigned value of given variable or NULL.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function getEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name) {
    
    $VariableValue = NULL;
    if (isset(self::$SessionMode) && isset(self::$SessionMode->Environment)) {
      $VariableValue = self::$SessionMode->Environment->getVariable($Agent, $name);
    }
    else {
      throw new AblePolecat_Mode_Exception("Cannot access variable '$name'. Environment is not initialized.");
    }
    return $VariableValue;
  }
  
  /**
   * Assign value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   * @param mixed $value Value of variable.
   *
   * @return bool TRUE if variable is set, otherwise FALSE.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function setEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name, $value) {
    $VariableSet = NULL;
    if (isset(self::$SessionMode) && isset(self::$SessionMode->Environment)) {
      $VariableSet = self::$SessionMode->Environment->setVariable($Agent, $name, $value);
    }
    else {
      throw new AblePolecat_Mode_Exception("Cannot access variable '$name'. Environment is not initialized.");
    }
    return $VariableSet;
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
        $ValidLink = (!is_a($Target, 'AblePolecat_Mode_User') &&
          !is_a($Target, 'AblePolecat_Mode_Host') &&
          !is_a($Target, 'AblePolecat_Mode_Application') &&
          !is_a($Target, 'AblePolecat_Mode_Server')
        );
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        $ValidLink = is_a($Target, 'AblePolecat_Mode_User');
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
    // Start or resume session.
    // Able Polecat does not use PHP session. But it does check the session global variable
    // to ensure that it is not tampered with by extension classes.
    //
    $this->initializeSessionSecurity();
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Session security initialized.');
    
    //
    // Start or resume session.
    //
    $this->Session = AblePolecat_Session::wakeup();
    
    //
    // Initialize user agent.
    //
    $UserAgent = AblePolecat_AccessControl_Agent_User::wakeup($this->Session);
    
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Session mode is initialized.');
  }
}