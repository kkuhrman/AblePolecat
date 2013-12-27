<?php
/**
 * @file: Mode/Server.php
 * Highest level in command processing chain of responsibility hierarchy.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Syslog.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode.php')));

class AblePolecat_Mode_Server extends AblePolecat_ModeAbstract {
  
  const UUID = '2621ce80-5df4-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Server Mode';
  
  /**
   * @var AblePolecat_Mode_Server Instance of singleton.
   */
  private static $Mode = NULL;
  
  /**
   * @var AblePolecat_AccessControl_AgentInterface
   */
  private $Agent;
  
  /**
   * @var AblePolecat_Database_Pdo
   */
  private $Database;
  
  /**
   * @var Array Information about state of the server database.
   */
  private $db_state;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $Environment;
  
  /********************************************************************************
   * Access control methods.
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
   * Command target methods.
   ********************************************************************************/
   
  /**
   * Execute the command and return the result of the action.
   *
   * @param AblePolecat_CommandInterface $Command The command to execute.
   */
  public function execute(AblePolecat_CommandInterface $Command) {
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
      case 'forward':
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Application');
        break;
      case 'reverse':
        $ValidLink = is_a($Target, 'AblePolecat_Server');
        break;
    }
    return $ValidLink;
  }
  
  /********************************************************************************
   * Resource access methods.
   ********************************************************************************/
  /**
   * Get access to application database.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return AblePolecat_DatabaseInterface.
   */
  public static function getDatabase() {
    
    $Database = NULL;
    if (isset($Subject) && ($this->Agent === $Subject)) {
    }
    else {
    }
    return $Database;
  }
  
  /**
   * Get information about state of application database at boot time.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   * @param mixed $param If set, a particular parameter.
   *
   * @return mixed Array containing all state data, or value of given parameter or FALSE.
   */
  public function getDatabaseState(AblePolecat_AccessControl_SubjectInterface $Subject = NULL, $param = NULL) {
    
    $state = NULL;
    
    if (isset($Subject)) {
      if ($Subject === $this) {
        if (isset($this->db_state)) {
          if (isset($param) && isset($this->db_state[$param])) {
            $state = $this->db_state[$param];
          }
          else {
            $state = $this->db_state;
          }
        }
      }
      else {
        if (isset($this->db_state)) {
          switch($param) {
            default:
              break;
            case 'name':
            case 'connected':
              isset($this->db_state[$param]) ? $state = $this->db_state[$param] : NULL;
              break;
          }
        }
      }
    }
    
    return $state;
  }
  
  /********************************************************************************
   * Error/exceptional handling methods.
   ********************************************************************************/
  
  /**
   * Handle errors triggered by child objects.
   */
  public static function handleError($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL) {
    
    $shutdown = (($errno == E_ERROR) || ($errno == E_USER_ERROR));
    
    //
    // Get error information
    //
    $msg = sprintf("Error in Able Polecat. %d %s", $errno, $errstr);
    isset($errfile) ? $msg .= " in $errfile" : NULL;
    isset($errline) ? $msg .= " line $errline" : NULL;
    
    //
    // @todo: perhaps better diagnostics.
    // serialize() is not supported for all types
    //
    // isset($errcontext) ? $msg .= ' : ' . serialize($errcontext) : NULL;
    // isset($errcontext) ? $msg .= ' : ' . get_class($errcontext) : NULL;
    
    //
    // Send error information to syslog
    //
    $type = AblePolecat_LogInterface::STATUS;
    switch($errno) {
      default:
        break;
      case E_USER_ERROR:
      case E_ERROR:
        $type = AblePolecat_LogInterface::ERROR;
        break;
      case E_USER_WARNING:
        $type = AblePolecat_LogInterface::WARNING;
        break;
    }
    AblePolecat_Log_Syslog::wakeup()->putMessage($type, $msg);
    if ($shutdown) {
      AblePolecat_Server::shutdown();
    }
    return $shutdown;
  }
  
  /**
   * Handle exceptions thrown by child objects.
   */
  public static function handleException(Exception $Exception) {
    $msg = sprintf("Unhandled exception (%d) in Able Polecat. %s line %d : %s", 
      $Exception->getCode(),
      $Exception->getFile(),
      $Exception->getLine(),
      $Exception->getMessage()
    );
    AblePolecat_Log_Syslog::wakeup()->putMessage($type, $msg);
  }
  
  /********************************************************************************
   * Caching methods.
   ********************************************************************************/
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    //
    // Default error/exception handling
    //
    set_error_handler(array('AblePolecat_Mode_Server', 'handleError'));
    set_exception_handler(array('AblePolecat_Mode_Server', 'handleException'));
    
    //
    // Access control agent (super user).
    //
    $this->Agent = AblePolecat_AccessControl_Agent_Server::wakeup($this);
    
    //
    // Load environment/configuration
    //
    //
    $this->Environment = AblePolecat_Environment_Server::wakeup($this->Agent);
    
    //
    // Load core database configuration settings.
    //
    $this->db_state = $this->Environment->getVariable($this->Agent, AblePolecat_Server::SYSVAR_CORE_DATABASE);
    $this->db_state['connected'] = FALSE;
    if (isset($this->db_state['dsn'])) {
      //
      // Attempt a connection.
      // @todo: access control
      //
      $this->Database = AblePolecat_Database_Pdo::wakeup($this);
      $DbUrl = AblePolecat_AccessControl_Resource_Locater::create($this->db_state['dsn']);
      $this->Database->open($this->Agent, $DbUrl);           
      $this->db_state['connected'] = TRUE;
    }
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   *
   * @return AblePolecat_Mode_Dev or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Mode)) {
      //
      // Only server can wakeup server mode.
      //
      if (isset($Subject) && is_a($Subject, 'AblePolecat_Server')) {
        //
        // Create instance of server mode.
        //
        self::$Mode = new AblePolecat_Mode_Server();
        
        //
        // Set chain of responsibility relationship
        //
        $Subject->setForwardCommandLink(self::$Mode);
        self::$Mode->setReverseCommandLink($Subject);
      }
      else {
      }
    }
    return self::$Mode;
  }
}