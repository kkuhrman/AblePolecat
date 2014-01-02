<?php
/**
 * @file: Mode/Server.php
 * Highest level in command processing chain of responsibility hierarchy.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Class.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Interface.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Syslog.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode.php')));

/**
 * Core Commands
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'DbQuery.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Log.php')));

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
   * @var AblePolecat_Registry_Class
   */
  private $ClassRegistry;
  
  /**
   * @var AblePolecat_Registry_Interface
   */
  private $InterfaceRegistry;
  
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
  
  /**
   * @var AblePolecat_Log_Pdo
   */
  private $Log;
  
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
   * Execute database query and return results.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql
   *
   * @return Array Results/rowset.
   */
  protected function executeDbQuery(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql) {
    
    //
    // @todo: handle invalid query
    //
    $QueryResult = array();
    
    if (isset($this->Database)) {
      $Stmt = $this->Database->prepareStatement($sql);
      if ($Stmt->execute()) {
        while ($result = $Stmt->fetch(PDO::FETCH_ASSOC)) {
          $QueryResult[] = $result;
        }
      }
    }
    return $QueryResult;
  }
  
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
        break;
      case 'ef797050-715c-11e3-981f-0800200c9a66':
        //
        // DbQuery
        //
        $QueryResult = $this->executeDbQuery($Command->getArguments());
        $Result = new AblePolecat_Command_Result($QueryResult, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        break;
      case '85fc7590-724d-11e3-981f-0800200c9a66':
        //
        // Log
        //
        self::$Mode->Log->putMessage($Command->getEventSeverity(), $Command->getEventMessage());
        switch($Command->getEventSeverity()) {
          default:
            $Result = new AblePolecat_Command_Result(NULL, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
            break;
          case AblePolecat_LogInterface::ERROR:
            //
            // Pass error messages on to server.
            //
            break;
        }
        break;
    }
    if (!isset($Result)) {
      //
      // Pass command to next link in chain of responsibility
      //
      $Result = $this->delegateCommand($Command);
    }
    return $Result;
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
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Application');
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        $ValidLink = is_a($Target, 'AblePolecat_Server');
        break;
    }
    return $ValidLink;
  }
  
  /********************************************************************************
   * Resource access methods.
   ********************************************************************************/
  
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
    AblePolecat_Command_Log::invoke(self::$Mode, $msg, $type);
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
    AblePolecat_Command_Log::invoke(self::$Mode, $msg, $type);
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
        self::$Mode = new AblePolecat_Mode_Server($Subject);
        
        //
        // Set chain of responsibility relationship
        //
        $Subject->setForwardCommandLink(self::$Mode);
        self::$Mode->setReverseCommandLink($Subject);
        
        //
        // Access control agent (super user).
        //
        self::$Mode->Agent = AblePolecat_AccessControl_Agent_Server::wakeup(self::$Mode);
        
        //
        // Load environment/configuration
        //
        //
        self::$Mode->Environment = AblePolecat_Environment_Server::wakeup(self::$Mode->Agent);
        
        //
        // Load core database configuration settings.
        //
        self::$Mode->db_state = self::$Mode->Environment->getVariable(self::$Mode->Agent, AblePolecat_Server::SYSVAR_CORE_DATABASE);
        self::$Mode->db_state['connected'] = FALSE;
        if (isset(self::$Mode->db_state['dsn'])) {
          //
          // Attempt a connection.
          // @todo: access control
          //
          self::$Mode->Database = AblePolecat_Database_Pdo::wakeup(self::$Mode);
          $DbUrl = AblePolecat_AccessControl_Resource_Locater::create(self::$Mode->db_state['dsn']);
          self::$Mode->Database->open(self::$Mode->Agent, $DbUrl);           
          self::$Mode->db_state['connected'] = TRUE;
        }
        
        //
        // Start logging to database.
        //
        self::$Mode->Log = AblePolecat_Log_Pdo::wakeup(self::$Mode);
        
        //
        // Load interface registry.
        //
        self::$Mode->InterfaceRegistry = AblePolecat_Registry_Interface::wakeup(self::$Mode);
        
        //
        // Load class registry.
        //
        self::$Mode->ClassRegistry = AblePolecat_Registry_Class::wakeup(self::$Mode);
      }
      else {
      }
    }
    return self::$Mode;
  }
}