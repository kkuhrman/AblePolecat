<?php
/**
 * @file      polecat/core/Mode/Server.php
 * @brief     Second highest level in command processing chain of responsibility hierarchy.
 *
 *
 * Server mode has the following duties:
 * 1. Act as binding access control arbitrator
 * 2. Load and configure server environment.
 * 3. Encapsulate core database.
 * 4. Handle shut down and redirection in the event of error
 * 5. Act as terminal/final command target
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'System.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Class.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Boot.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Syslog.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Version.php')));

/**
 * Core Commands
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'AccessControl', 'Authenticate.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'DbQuery.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetAccessToken.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetAgent.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetRegistry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Log.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Shutdown.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Server', 'Version.php')));

class AblePolecat_Mode_Server extends AblePolecat_ModeAbstract {
  
  const UUID = '2621ce80-5df4-11e3-949a-0800200c9a66';
  const NAME = 'AblePolecat_Mode_Server';
  
  /**
   * AblePolecat_Mode_Server Instance of singleton.
   */
  private static $ServerMode;
    
  /**
   * @var AblePolecat_Log_Boot
   */
  private $BootLog;
  
  /**
   * @var AblePolecat_Registry_Class
   */
  private $ClassRegistry;
  
  /**
   * @var AblePolecat_Registry_Interface
   */
  // private $InterfaceRegistry;
  
  /**
   * @var AblePolecat_Database_Pdo
   */
  private $CoreDatabase;
  
  /**
   * @var Array Information about state of the server database.
   */
  private $db_state;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $ServerEnvironment;
  
  /**
   * @var AblePolecat_Log_Pdo
   */
  private $Log;
    
  /**
   * @var int Error display directive.
   */
  private static $display_errors;
  
  /**
   * @var int Error reporting directive.
   */
  private static $report_errors;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
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
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$ServerMode)) {      
      //
      // Create instance of singleton.
      //
      self::$ServerMode = new AblePolecat_Mode_Server();
    }
    return self::$ServerMode;
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
        // End of CoR. FAIL.
        //
        $Result = new AblePolecat_Command_Result();
        break;
      case AblePolecat_Command_AccessControl_Authenticate::UUID:
        //
        // Authenticate user.
        //
        $Result = new AblePolecat_Command_Result("Core database is not available.", AblePolecat_Command_Result::RESULT_RETURN_FAIL);
        if (isset($this->CoreDatabase)) {
          $grants = $this->CoreDatabase->showGrants($Command->getUserName(), $Command->getPassword(), 'polecat');
          if (isset($grants)) {
            $Result = new AblePolecat_Command_Result($grants, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
          }
        }
        break;
      case AblePolecat_Command_GetAccessToken::UUID:
        //
        // Request for access to restricted resource.
        // In server mode, the only restricted resource is server database.
        // Typically only passed to server mode prior to install or utility execution.
        //
        $Result = new AblePolecat_Command_Result(NULL, AblePolecat_Command_Result::RESULT_RETURN_FAIL);
        break;
      case AblePolecat_Command_GetAgent::UUID:
        //
        // get agent
        //
        $Result = new AblePolecat_Command_Result($this->getAgent(), AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        break;
      case AblePolecat_Command_DbQuery::UUID:
        //
        // DbQuery
        //
        $QueryResult = $this->executeDbQuery($Command->getArguments());
        count($QueryResult) ? $success = AblePolecat_Command_Result::RESULT_RETURN_SUCCESS : $success = AblePolecat_Command_Result::RESULT_RETURN_FAIL;
        $Result = new AblePolecat_Command_Result($QueryResult, $success);
        break;
      case AblePolecat_Command_GetRegistry::UUID:
        switch($Command->getArguments()) {
          default:
            break;
          case 'AblePolecat_Registry_Class':
            $ClassRegistry = $this->getClassRegistry();
            if (isset($ClassRegistry)) {
              $Result = new AblePolecat_Command_Result($ClassRegistry, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
            }
            break;
        }
        break;
      case AblePolecat_Command_Log::UUID:
        //
        // Log
        //
        if (isset($this->Log)) {
          $this->Log->putMessage($Command->getEventSeverity(), $Command->getEventMessage());
        }
        else {
          AblePolecat_Log_Syslog::wakeup()->putMessage($Command->getEventSeverity(), $Command->getEventMessage());
        }
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
      case AblePolecat_Command_Shutdown::UUID:
        self::shutdown($Command->getStatus());
        break;
      case AblePolecat_Command_Server_Version::UUID:
        $Version = AblePolecat_Version::getVersion(TRUE, 'text');
        $Result = new AblePolecat_Command_Result($Version, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        break;
    }
    return $Result;
  }
  
  /********************************************************************************
   * Error and exception handling functions.
   ********************************************************************************/
  
  /**
   * Configure error reporting/handling.
   */
  private function initializeErrorReporting() {
    self::$report_errors = E_ALL;
    self::$display_errors = 0;
    if (isset($_REQUEST['display_errors'])) {
      $display_errors = strval($_REQUEST['display_errors']);
      switch ($display_errors) {
        default:
          self::$display_errors = E_ALL;
          break;
        case 'strict':
          self::$report_errors = E_STRICT;
          self::$display_errors = E_STRICT;
          break;
      }
      
      //
      // Error settings for local development only
      //
      error_reporting(self::$report_errors);
      ini_set('display_errors', self::$display_errors);
      
      //
      // Open the boot log.
      //
      $this->BootLog = AblePolecat_Log_Boot::wakeup($this->getAgent());
    }
    else {
      //
      // Error settings for production web server
      //
      error_reporting(self::$report_errors);
      ini_set('display_errors', self::$display_errors);
      $this->BootLog = NULL;
    }
    //
    // Default error/exception handling
    //
    set_error_handler(array('AblePolecat_Mode_Server', 'handleError'));
    set_exception_handler(array('AblePolecat_Mode_Server', 'handleException'));
  }
  
  /**
   * Handle errors triggered by child objects.
   */
  public static function handleError($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL) {
    
    $shutdown = (($errno == E_ERROR) || ($errno == E_USER_ERROR));
    
    //
    // Get error information
    //
    $msg = sprintf("Error in Able Polecat. %d %s", $errno, $errstr);
    
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
    
    $errorFile = str_replace("\\", "\\\\", $errfile);
    $errorLine = $errline;    
    $errorMessage = $msg;
    
    //
    // All errors get dumped to boot log.
    //
    self::logBootMessage(AblePolecat_LogInterface::ERROR, $errorMessage);
    
    //
    // Log to database if connected.
    //
    if (self::getActiveCoreDatabaseName()) {
      $sql = __SQL()->          
        insert(
          'errorType',
          'errorFile', 
          'errorLine', 
          'errorClass', 
          'errorFunction',
          'errorMessage')->
        into('error')->
        values(
          $type,
          $errorFile,
          $errorLine,
          __CLASS__,
          __FUNCTION__,
          $errorMessage
      );
      self::$ServerMode->executeDbQuery($sql);
    }
    else {
      //
      // Apparently, no other log facility was available to handle the message
      //
      AblePolecat_Log_Syslog::wakeup(AblePolecat_AccessControl_Agent_System::wakeup())->putMessage($type, $msg);
    }
    
    //
    // @todo: give user, application modes chance to do something with shut down command.
    //
    
    if ($shutdown && (self::$display_errors != 0)) {
      $reason = 'Critical Error';
      $code = $errno;
      self::shutdown($code);
    }
    else if (self::$display_errors != 0) {
      //
      // User induced script to vomit error on screen.
      //
      print('<p>display_errors set to ' . self::$display_errors . '</p>');
      print('<p>' . 
        $msg . 
        '<ul><li>' .
        $errfile . 
        '</li><li>' .
        $errline .
        '</li></ul></p>'
      );
      exit($errno);
    }
    return $shutdown;
  }
  
  /**
   * Handle exceptions thrown by child objects.
   * 
   * @todo: hand control back to the server or otherwise fail gracefully. no WSOD
   */
  public static function handleException(Exception $Exception) {
    
    //
    // Log exception to database.
    //
    $errorFile = str_replace("\\", "\\\\", $Exception->getFile());
    $errorLine = $Exception->getLine();    
    $errorMessage = $Exception->getMessage();
    
    //
    // All unhandled exceptions get dumped to boot log.
    //
    self::logBootMessage(AblePolecat_LogInterface::ERROR, $errorMessage);
    
    //
    // Log to database if connected.
    //
    if (self::getActiveCoreDatabaseName()) {
      $sql = __SQL()->          
        insert(
          'errorType',
          'errorFile', 
          'errorLine', 
          'errorClass', 
          'errorFunction',
          'errorMessage')->
        into('error')->
        values(
          'exception',
          $errorFile,
          $errorLine,
          __CLASS__,
          __FUNCTION__,
          $errorMessage
      );
      self::$ServerMode->executeDbQuery($sql);
    }
    else {
      //
      // Apparently, no other log facility was available to handle the message
      //
      AblePolecat_Log_Syslog::wakeup(AblePolecat_AccessControl_Agent_System::wakeup())->putMessage(AblePolecat_LogInterface::WARNING, $errorMessage);
    }
    
    //
    // @todo: give user, application modes chance to do something with shut down command.
    //
    
    //
    // Send shut down command to server
    //
    $reason = 'Unhandled exception';
    $code = $Exception->getCode();
    self::shutdown($code);
  }
  
  /********************************************************************************
   * Database and logging functions.
   ********************************************************************************/
  
  /**
   * Initialize connection to core database.
   */
  private function initializeCoreDatabase() {
    if (isset($this->ServerEnvironment)) {
      $this->db_state = $this->ServerEnvironment->getVariable($this->getAgent(), AblePolecat_Environment_Server::SYSVAR_CORE_DATABASE);
      $this->db_state['connected'] = FALSE;
      if (isset($this->db_state['dsn'])) {
        //
        // Attempt a connection.
        //
        $this->CoreDatabase = AblePolecat_Database_Pdo::wakeup($this->getAgent());
        $DbUrl = AblePolecat_AccessControl_Resource_Locater_Dsn::create($this->db_state['dsn']);
        $this->db_state['connected'] = $this->CoreDatabase->open($this->getAgent(), $DbUrl);
      }
          
      //
      // Stop loading if there is no connection to the core database.
      //
      $this->ServerEnvironment->setVariable(
        $this->getAgent(), 
        AblePolecat_Environment_Server::SYSVAR_CORE_DATABASE,
        $this->db_state
      );
      if ($this->db_state['connected']) {
        //
        // Start logging to database.
        //
        $this->Log = AblePolecat_Log_Pdo::wakeup($this->getAgent());
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Connection to core database established.');
      }
      else {
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Connection to core database is not established.');
      }
    }
    else {
      throw new AblePolecat_Mode_Exception('Boot sequence violation: Cannot initialize core database before server environment.',
        AblePolecat_Error::BOOT_SEQ_VIOLATION
      );
    }
  }
  
  /**
   * Write information to the boot log if it is open.
   *
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   * 
   * @return mixed Message as sent, if written, otherwise FALSE.
   */
  private function putBootMessage($type, $msg) {    
    if (isset($this->BootLog)) {
      $this->BootLog->putMessage($type, $msg);
    }
  }
  
  /**
   * @return mixed Name of core database or FALSE if no connection exists.
   */
  public static function getActiveCoreDatabaseName() {
    
    $activeCoreDatabaseName = FALSE;
    
    if (isset(self::$ServerMode) && isset(self::$ServerMode->db_state['connected']) && self::$ServerMode->db_state['connected']) {
      $activeCoreDatabaseName = self::$ServerMode->db_state['name'];
    }
    return $activeCoreDatabaseName;
  }
  
  /**
   * Get information about state of application database at boot time.
   *
   * @param AblePolecat_Command_TargetInterface $Subject
   * @param mixed $param If set, a particular parameter.
   *
   * @return mixed Array containing all state data, or value of given parameter or FALSE.
   */
  public function getDatabaseState(AblePolecat_Command_TargetInterface $Subject = NULL, $param = NULL) {
    
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
          if (isset($param) && isset($this->db_state[$param])) {
            switch($param) {
              default:
                break;
              case 'name':
              case 'connected':
                isset($this->db_state[$param]) ? $state = $this->db_state[$param] : NULL;
                break;
            }
          }
          else {
            $state = array(
              'name' => $this->db_state['name'],
              'connected' => $this->db_state['connected']
            );
          }
        }
      }
    }
    
    return $state;
  }
  
  /**
   * Write information to the boot log if it is open.
   *
   * @param string $type STATUS | WARNING | ERROR.
   * @param string $msg  Body of message.
   * 
   * @return mixed Message as sent, if written, otherwise FALSE.
   */
  public static function logBootMessage($type, $msg) {
    
    $writeResult = FALSE;
    if (isset(self::$ServerMode)) {
      self::$ServerMode->putBootMessage($type, $msg);
      $writeResult = $msg;
    }
    return $writeResult;
  }
  
  /**
   * Execute database query and return results.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql
   *
   * @return Array Results/rowset.
   */
  private function executeDbQuery(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql) {
    
    //
    // @todo: handle invalid query
    //
    $QueryResult = array();
    
    //
    // Server mode can only execute query against core database.
    //
    if (isset($this->db_state['connected']) && $this->db_state['connected']) {
      $databaseName = $sql->getDatabaseName();
      isset($this->db_state['name']) ? $coreDatabaseName = $this->db_state['name'] : $coreDatabaseName = NULL;
      if (isset($databaseName) && ($databaseName != $coreDatabaseName)) {
        $message = "Server mode can only execute query against core database. [$databaseName] given.";
        throw new AblePolecat_Database_Exception($message);
      }
      
      if (isset($this->CoreDatabase)) {
        switch ($sql->getDmlOp()) {
          default:
            $QueryResult = $this->CoreDatabase->execute($sql);
            break;
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::SELECT:
            $QueryResult = $this->CoreDatabase->query($sql);
            break;
        }
      }
    }
    return $QueryResult;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return AblePolecat_Registry_Class.
   */
  protected function getClassRegistry() {
    
    if (!isset($this->ClassRegistry)) {
      //
      // Load class registry.
      //
      $this->ClassRegistry = AblePolecat_Registry_Class::wakeup($this->getAgent());
    }
    return $this->ClassRegistry;
  }
  
  /**
   * Shut down Able Polecat server and send HTTP response.
   *
   * @param int $status Return code.
   */
  protected static function shutdown($status = 0) {
    exit($status);
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
        // $validLink = is_a($Target, 'AblePolecat_Host');
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        //
        // Server must always be the end of the CoR.
        //
        break;
    }
    return $ValidLink;
  }
      
  /**
   * Extends constructor.
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // Error and exception handling.
    //
    $this->initializeErrorReporting();
    $this->putBootMessage(AblePolecat_LogInterface::STATUS, 'Error reporting initialized.');
    
    //
    // Load class registry.
    //
    $this->ClassRegistry = AblePolecat_Registry_Class::wakeup($this->getAgent());
    
    //
    // Load environment/configuration
    //
    //
    $this->ServerEnvironment = AblePolecat_Environment_Server::wakeup($this->getAgent());
    
    //
    // Load core database configuration settings.
    //
    $this->initializeCoreDatabase();
    
    //
    // Finalize initial logging.
    //
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, sprintf("Able Polecat core version is %s", AblePolecat_Version::getVersion(TRUE, 'text')));
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Server mode is initialized.');
  }
}