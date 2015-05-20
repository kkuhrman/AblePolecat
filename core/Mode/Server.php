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
   * @var AblePolecat_Database_Pdo
   */
  private $CoreDatabase;
  
  /**
   * @var Array Core server database connection settings.
   */
  private $CoreDatabaseConnectionSettings;
    
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $ServerEnvironment;
  
  /**
   * @var AblePolecat_Log_Pdo
   */
  private $Log;
  
  /**
   * @var bool Flag indicates if project database needs to be installed.
   */
  private $installMode;
  
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
      //
      // Flush output buffer.
      //
      ob_end_flush();
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Output buffering flushed.');
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
      // Attempt to connect to project database.
      //
      $CoreDatabase = self::$ServerMode->getCoreDatabase();
      if (isset($CoreDatabase) && $CoreDatabase->ready()) {
        //
        // Project database is initialized and ready.
        //
        self::reportBootState(self::BOOT_STATE_CONFIG, 'Host configuration initialized.');
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, sprintf("Connected to project database [%s].",
          $CoreDatabase->getName()
        ));
      }
      else {
        //
        // Project database is not ready. Trigger error if not install mode.
        // Peek at HTTP request.
        //
        isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
        switch ($method) {
          default:
            break;
          case 'GET':
            //
            // Verify that the local project configuration file is writeable.
            //
            $localProjectConfFilePath = AblePolecat_Mode_Config::getLocalProjectConfFilePath();
            self::$ServerMode->installMode = is_writeable($localProjectConfFilePath);
            break;
          case 'POST':
            if (isset($_POST[AblePolecat_Transaction_RestrictedInterface::ARG_REFERER])) {
              $referer = $_POST[AblePolecat_Transaction_RestrictedInterface::ARG_REFERER];
              if ($referer === AblePolecat_Resource_Restricted_Install::UUID) {
                self::$ServerMode->installMode = TRUE;
              }
            }
            break;
        }
        if (self::$ServerMode->installMode === FALSE) {
          AblePolecat_Command_Chain::triggerError('Boot sequence violation: Project database is not ready.');
        }
      }

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
        if (isset($this->CoreDatabase)) {
          $grants = $this->CoreDatabase->showGrants($Command->getUserName(), $Command->getPassword(), 'polecat');
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
    if (AblePolecat_Mode_Server::coreDatabaseIsReady()) {
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
    if (AblePolecat_Mode_Server::coreDatabaseIsReady()) {
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
  
  /********************************************************************************
   * Database functions.
   ********************************************************************************/
  
  /**
   * Attempt to establish a connection to core database with user credentials.
   *
   * This function exists for one purpose, which is to establish a connection 
   * to the core project database in the case where the DSN in the local project
   * configuration file does not contain the correct user name and password;
   * for example, before installing an Able Polecat project or after overwriting
   * the local project configuration file with the master.
   *
   * @throw AblePolecat_Database_Exception If system user is already connected.
   */
  public static function connectUserToCoreDatabase() {
    
    $connected = FALSE;
    
    if (isset(self::$ServerMode)) {
      if (isset(self::$ServerMode->CoreDatabase) && self::$ServerMode->CoreDatabase->ready()) {
        throw new AblePolecat_Database_Exception('System user is connected to core database.');
      }
      else {
        self::$ServerMode->CoreDatabase = NULL;
        $User = AblePolecat_AccessControl_Agent_User::wakeup();
        self::$ServerMode->CoreDatabase = AblePolecat_Database_Pdo::wakeup($User);
        $dbErrors = self::$ServerMode->CoreDatabase->flushErrors();
        $connected = self::$ServerMode->CoreDatabase->ready();
      }
    }
    return $connected;
  }
  
  public static function installCoreDatabase() {
    
    $installed = FALSE;
    
    if (!isset(self::$ServerMode)) {
      AblePolecat_Debug::kill(self::$ServerMode);
      if ($self::$ServerMode->installMode) {
        if (isset(self::$ServerMode->CoreDatabase)) {
          if (self::$ServerMode->CoreDatabase->ready()) {
            AblePolecat_Database_Schema::install(self::$ServerMode->CoreDatabase);
            $dbErrors = self::$ServerMode->CoreDatabase->flushErrors();
            foreach($dbErrors as $errorNumber => $error) {
              $error = AblePolecat_Database_Pdo::getErrorMessage($error);
              AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, $error);
            }
          }
        }
      }
    }
    return $installed;
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
    if (isset($this->CoreDatabase) && $this->CoreDatabase->ready()) {
      $queryDatabaseName = $sql->getDatabaseName();
      if (isset($queryDatabaseName) && ($queryDatabaseName != $this->CoreDatabase->getName())) {
        $message = sprintf("Server mode can only execute query against core database ([%s]). [$queryDatabaseName] given.", $this->CoreDatabase->getName());
        throw new AblePolecat_Database_Exception($message);
      }
      
      switch ($sql->getDmlOp()) {
        default:
          $QueryResult = $this->CoreDatabase->execute($sql);
          break;
        case AblePolecat_QueryLanguage_Statement_Sql_Interface::SELECT:
          $QueryResult = $this->CoreDatabase->query($sql);
          break;
      }
      if (0 == count($QueryResult)) {
        $dbErrors = $this->CoreDatabase->flushErrors();
        foreach($dbErrors as $errorNumber => $error) {
          $error = AblePolecat_Database_Pdo::getErrorMessage($error);
          AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, $error);
        }
      }
    }
    return $QueryResult;
  }
  
  /**
   * @return boolean TRUE if core database connection is established, otherwise FALSE.
   */
  public static function coreDatabaseIsReady() {
    
    $dbReady = FALSE;
    if (isset(self::$ServerMode) && isset(self::$ServerMode->CoreDatabase)) {
      $dbReady = self::$ServerMode->CoreDatabase->ready();
    }
    return $dbReady;
  }
  
  /**
   * Initialize connection to core database.
   *
   * More than one application database can be defined in server conf file. 
   * However, only ONE application database can be active per server mode. 
   * If 'mode' attribute is empty, polecat will assume any mode. Otherwise, 
   * database is defined for given mode only. The 'use' attribute indicates 
   * that the database should be loaded for the respective server mode. Polecat 
   * will scan database definitions until it finds one suitable for the current 
   * server mode where the 'use' attribute is set. 
   * @code
   * <database id="core" name="polecat" mode="server" use="1">
   *  <dsn>mysql://username:password@localhost/databasename</dsn>
   * </database>
   * @endcode
   *
   * Only one instance of core (server mode) database can be active.
   * Otherwise, Able Polecat stops boot and throws exception.
   *
   */
  public function getCoreDatabase() {
    
    if (!isset($this->CoreDatabase)) {
      //
      // Core database connection settings.
      //
      $this->CoreDatabaseConnectionSettings['connected'] = FALSE;
      
      //
      // Get DSN from local project configuration file.
      //
      $localProjectConfFile = AblePolecat_Mode_Config::getLocalProjectConfFile();
      $coreDatabaseElementId = AblePolecat_Mode_Config::getCoreDatabaseId();
      $Node = AblePolecat_Dom::getElementById($localProjectConfFile, $coreDatabaseElementId);
      if (isset($Node)) {
        $this->CoreDatabaseConnectionSettings['name'] = $Node->getAttribute('name');
        foreach($Node->childNodes as $key => $childNode) {
          if($childNode->nodeName == 'polecat:dsn') {
            $this->CoreDatabaseConnectionSettings['dsn'] = $childNode->nodeValue;
            break;
          }
        }
      }
      else {
        AblePolecat_Command_Chain::triggerError("Local project configuration file does not contain a locater for $coreDatabaseElementId.");
      }
      
      if (isset($this->CoreDatabaseConnectionSettings['dsn'])) {
        //
        // Assign database client role to system user.
        //
        $SystemUser = $this->getAgent();
        $DatabaseClientRole = AblePolecat_AccessControl_Role_Client_Database::wakeup($SystemUser);
        $DatabaseLocater = AblePolecat_AccessControl_Resource_Locater_Dsn::create($this->CoreDatabaseConnectionSettings['dsn']);
        $DatabaseClientRole->setResourceLocater($DatabaseLocater);
        $SystemUser->assignActiveRole($DatabaseClientRole);
        
        //
        // Attempt a connection.
        // Polecat will use locater and token associated with db client role 
        // (assigned above) to establish connection.
        //
        $this->CoreDatabase = AblePolecat_Database_Pdo::wakeup($this->getAgent());
        // AblePolecat_Debug::kill($this->CoreDatabase);
      }
    }
    return $this->CoreDatabase;
  }
  
  /**
   * @var Array Core server database connection settings.
   */
  public static function getCoreDatabaseConnectionSettings() {
    $CoreDatabaseConnectionSettings = NULL;
    if (isset(self::$ServerMode)) {
      $CoreDatabaseConnectionSettings = self::$ServerMode->CoreDatabaseConnectionSettings;
    }
    return $CoreDatabaseConnectionSettings;
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
    // Turn on output buffering.
    //
    ob_start();
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Output buffering started.');
    
    //
    // Register cleanup() as a shut down function.
    //
    register_shutdown_function(array(__CLASS__, 'cleanup'));
    
    //
    // Error and exception handling.
    //
    $this->initializeErrorReporting();
    
    $this->CoreDatabase = NULL;
    $this->CoreDatabaseConnectionSettings = array();
    $this->installMode = FALSE;
  }
}