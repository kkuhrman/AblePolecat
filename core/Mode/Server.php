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
 * @version   0.7.2
 */

/**
 * System user and roles.
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'User', 'System.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role', 'Client', 'Database.php')));

/**
 * Other member properties.
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Version.php')));

/**
 * Core Commands
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'AccessControl', 'Authenticate.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Database', 'Query.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetAccessToken.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetAgent.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetRegistry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Log.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Shutdown.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Server', 'Version.php')));

/**
 * Logging.
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Boot.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Syslog.php')));
 
/**
 * Base class.
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Config.php')));

class AblePolecat_Mode_Server extends AblePolecat_ModeAbstract {
  
  const UUID = '2621ce80-5df4-11e3-949a-0800200c9a66';
  const NAME = 'AblePolecat_Mode_Server';
  
  /**
   * AblePolecat_Mode_Server Instance of singleton.
   */
  private static $ServerMode;
    
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $ServerEnvironment;
  
  /**
   * @var AblePolecat_Log_Pdo
   */
  private $Log;
  
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
    try {
      parent::sleep();
    }
    catch (AblePolecat_Exception $Exception) {
    }
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
      
      //
      // Check critical configuration settings.
      //
      $ConfigMode = AblePolecat_Mode_Config::wakeup();
      
      //
      // Establish config mode as reverse command target.
      //
      $CommandChain = AblePolecat_Command_Chain::wakeup();
      $CommandChain->setCommandLink($ConfigMode, self::$ServerMode);

      //
      // Load environment/configuration
      //
      //
      self::$ServerMode->ServerEnvironment = AblePolecat_Environment_Server::wakeup(self::$ServerMode->getAgent());
       
      //
      // Finalize initial logging.
      //
      self::logBootMessage(AblePolecat_LogInterface::STATUS, sprintf("Able Polecat core version is %s", AblePolecat_Version::getVersion(TRUE, 'text')));
      self::logBootMessage(AblePolecat_LogInterface::STATUS, 'Server mode initialized.');
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
        $CoreDatabase = AblePolecat_Mode_Config::wakeup()->getCoreDatabase();
        if (isset($CoreDatabase)) {
          $grants = $CoreDatabase->showGrants($Command->getUserName(), $Command->getPassword(), 'polecat');
          if (isset($grants)) {
            $Result = new AblePolecat_Command_Result($grants, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
          }
        }
        else {
          $Result = new AblePolecat_Command_Result("Core database is not available.", AblePolecat_Command_Result::RESULT_RETURN_FAIL);
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
      case AblePolecat_Command_Database_Query::UUID:
        //
        // DbQuery
        //
        $QueryResult = $this->executeDbQuery($Command->getArguments());
        count($QueryResult) ? $success = AblePolecat_Command_Result::RESULT_RETURN_SUCCESS : $success = AblePolecat_Command_Result::RESULT_RETURN_FAIL;
        $Result = new AblePolecat_Command_Result($QueryResult, $success);
        break;
      case AblePolecat_Command_GetRegistry::UUID:
        $Registry = self::getEnvironmentVariable($this->getAgent(), $Command->getArguments());
        if (isset($Registry)) {
          $Result = new AblePolecat_Command_Result($Registry, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
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
        self::shutdown(
          $Command->getReason(),
          $Command->getMessage(),
          $Command->getStatus()
        );
        break;
      case AblePolecat_Command_Server_Version::UUID:
        $Version = AblePolecat_Version::getVersion(TRUE, 'text');
        $Result = new AblePolecat_Command_Result($Version, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        break;
    }
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
    if (isset(self::$ServerMode) && isset(self::$ServerMode->ServerEnvironment)) {
      $VariableValue = self::$ServerMode->ServerEnvironment->getVariable($Agent, $name);
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
    if (isset(self::$ServerMode) && isset(self::$ServerMode->ServerEnvironment)) {
      $VariableSet = self::$ServerMode->ServerEnvironment->setVariable($Agent, $name, $value);
    }
    else {
      throw new AblePolecat_Mode_Exception("Cannot access variable '$name'. Environment is not initialized.");
    }
    return $VariableSet;
  }
     
  /**
   * Shut down Able Polecat server and send HTTP response.
   *
   * @param string  $reason   Reason for shut down.
   * @param string  $message  Message associated with shut down request.
   * @param int     $status   Return code.
   */
  public static function shutdown($reason, $message, $status = 0) {
    //
    // Error resource.
    //
    $Invoker = AblePolecat_AccessControl_Agent_User_System::wakeup();
    $Resource = AblePolecat_Resource_Core_Factory::wakeup(
      $Invoker,
      'AblePolecat_Resource_Core_Error',
      $reason, 
      $message, 
      $status
    );
    
    //
    // Response registration.
    //
    $ResponseRegistration = AblePolecat_Registry_Entry_DomNode_Response::create();
    $ResponseRegistration->resourceId = AblePolecat_Resource_Core_Error::UUID; 
    $ResponseRegistration->statusCode = 200;
    $ResponseRegistration->id = AblePolecat_Message_Response_Xml::UUID;
    $ResponseRegistration->name = 'AblePolecat_Message_Response_Xml';
    $ResponseRegistration->classId = AblePolecat_Message_Response_Xml::UUID;
    
    //
    // Create and send response.
    //
    $Response = AblePolecat_Message_Response_Xml::create($ResponseRegistration);
    $Response->setEntityBody($Resource);
    $Response->send();
    
    //
    // Exit.
    //
    parent::shutdown($reason, $message, $status);
  }
  
  /********************************************************************************
   * Error and exception handling functions.
   ********************************************************************************/
  
  /**
   * Configure error reporting/handling.
   */
  protected function initializeErrorReporting() {
    
    parent::initializeErrorReporting();
    
    //
    // Default error/exception handling
    //
    set_error_handler(array('AblePolecat_Mode_Server', 'handleError'));
    set_exception_handler(array('AblePolecat_Mode_Server', 'handleException'));
    self::reportBootState(self::BOOT_STATE_ERR_INIT, 'Error reporting initialized.');
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
    if (AblePolecat_Mode_Config::coreDatabaseIsReady()) {
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
      AblePolecat_Log_Syslog::wakeup(AblePolecat_AccessControl_Agent_User_System::wakeup())->putMessage($type, $msg);
    }
    
    //
    // shut down and send response
    //
    $reason = 'Critical Error';
    AblePolecat_Command_Shutdown::invoke(
      self::$ServerMode->getDefaultCommandInvoker(), 
      $reason,
      $errorMessage,
      $errno
    );
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
    if (AblePolecat_Mode_Config::coreDatabaseIsReady()) {
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
      AblePolecat_Log_Syslog::wakeup(AblePolecat_AccessControl_Agent_User_System::wakeup())->putMessage(AblePolecat_LogInterface::WARNING, $errorMessage);
    }
    
    //
    // @todo: give user, application modes chance to do something with shut down command.
    //
    
    //
    // Send shut down command to server
    //
    $reason = 'Unhandled exception';
    $code = $Exception->getCode();
    self::shutdown(
      $reason,
      $errorMessage,
      $code
    );
  }
  
  /********************************************************************************
   * Database and logging functions.
   ********************************************************************************/
  
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
    
    $CoreDatabase = AblePolecat_Mode_Config::wakeup()->getCoreDatabase();
    
    //
    // Server mode can only execute query against core database.
    //
    if (isset($CoreDatabase) && $CoreDatabase->ready()) {
      $coreDatabaseName = trim($CoreDatabase->getLocater()->getPathname(), AblePolecat_Message_RequestInterface::URI_SLASH);
      $queryDatabaseName = $sql->getDatabaseName();
      if (isset($queryDatabaseName) && ($queryDatabaseName != $coreDatabaseName)) {
        $message = "Server mode can only execute query against core database ([$coreDatabaseName]). [$queryDatabaseName] given.";
        throw new AblePolecat_Database_Exception($message);
      }
      
      if (isset($CoreDatabase)) {
        switch ($sql->getDmlOp()) {
          default:
            $QueryResult = $CoreDatabase->execute($sql);
            break;
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::SELECT:
            $QueryResult = $CoreDatabase->query($sql);
            break;
        }
        if (0 == count($QueryResult)) {
          $dbErrors = $CoreDatabase->flushErrors();
          foreach($dbErrors as $errorNumber => $error) {
            $error = AblePolecat_Database_Pdo::getErrorMessage($error);
            AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, $error);
          }
        }
      }
    }
    return $QueryResult;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
    
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
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Config');
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
    // Register cleanup() as a shut down function.
    //
    register_shutdown_function(array(__CLASS__, 'cleanup'));
    
    //
    // Error and exception handling.
    //
    $this->initializeErrorReporting();
  }
}