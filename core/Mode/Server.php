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
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Administrator.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Class.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Boot.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Log', 'Syslog.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode.php')));

/**
 * Core Commands
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'DbQuery.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetAccessToken.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetAgent.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'GetRegistry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Log.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Shutdown.php')));

abstract class AblePolecat_Mode_ServerAbstract extends AblePolecat_ModeAbstract {
  
  const UUID = '2621ce80-5df4-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Server Mode';
  
  /**
   * @var AblePolecat_AccessControl_AgentInterface
   */
  private $AdministratorAgent;
  
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
   * @var AblePolecat_AccessControl_AgentInterface
   */
  private $UserAgent;
  
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
        break;
      case 'bed41310-2174-11e4-8c21-0800200c9a66':
        //
        // Check if given agent has requested permission for given resource.
        //
        if ($this->getAgent()->hasPermission($this, $Command->getAgentId(), $Command->getResourceId(), $Command->getConstraintId())) {
          //
          // @todo: Access is permitted. Get security token.
          //
          $SecurityToken = '@todo';
          $Result = new AblePolecat_Command_Result($SecurityToken, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        }
        else {
          $Result = new AblePolecat_Command_Result(NULL, AblePolecat_Command_Result::RESULT_RETURN_FAIL);
        }
        break;
      case '54d2e7d0-77b9-11e3-981f-0800200c9a66':
        //
        // get agent
        //
        $Agent = $this->getAgent()->getAgent($Command->getInvoker());
        $Result = new AblePolecat_Command_Result($Agent, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        break;
      case 'ef797050-715c-11e3-981f-0800200c9a66':
        //
        // DbQuery
        //
        $QueryResult = $this->executeDbQuery($Command->getArguments());
        count($QueryResult) ? $success = AblePolecat_Command_Result::RESULT_RETURN_SUCCESS : $success = AblePolecat_Command_Result::RESULT_RETURN_FAIL;
        $Result = new AblePolecat_Command_Result($QueryResult, $success);
        break;
      case 'c7587ad0-74a4-11e3-981f-0800200c9a66':
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
      case '85fc7590-724d-11e3-981f-0800200c9a66':
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
   * @return AblePolecat_AccessControl_Agent_Administrator
   */
  private function getAgent() {
    if (!isset($this->AdministratorAgent)) {
      throw new AblePolecat_Mode_Exception('Administrator agent is not available.');
    }
    return $this->AdministratorAgent;
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
  
  /**
   * @return AblePolecat_Registry_Class.
   */
  protected function getClassRegistry() {
    
    if (!isset($this->ClassRegistry)) {
      //
      // Load class registry.
      //
      $this->ClassRegistry = AblePolecat_Registry_Class::wakeup($this);
    }
    return $this->ClassRegistry;
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
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        $ValidLink = is_a($Target, 'AblePolecat_Host');
        break;
    }
    return $ValidLink;
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
   * @return AblePolecat_AccessControl_AgentInterface
   */
  public function getUserAgent() {
    if (!isset($this->UserAgent)) {
      $this->UserAgent = $this->getAgent()->getAgent($this);
    }
    return $this->UserAgent;
  }
    
  /**
   * Extends constructor.
   */
  protected function initialize() {
    
    //
    // Load class registry.
    //
    $this->ClassRegistry = AblePolecat_Registry_Class::wakeup($this);
    
    //
    // Access control agent (super user).
    //
    $Host = $this->getReverseCommandLink();
    $this->AdministratorAgent = AblePolecat_AccessControl_Agent_Administrator::wakeup($Host);
    
    //
    // Load environment/configuration
    //
    //
    $this->ServerEnvironment = AblePolecat_Environment_Server::wakeup($this->AdministratorAgent);
    
    //
    // Load core database configuration settings.
    //
    $this->db_state = $this->ServerEnvironment->getVariable($this->AdministratorAgent, AblePolecat_Environment_Server::SYSVAR_CORE_DATABASE);
    $this->db_state['connected'] = FALSE;
    if (isset($this->db_state['dsn'])) {
      //
      // Attempt a connection.
      // @todo: access control
      //
      $this->CoreDatabase = AblePolecat_Database_Pdo::wakeup($this);
      $DbUrl = AblePolecat_AccessControl_Resource_Locater_Dsn::create($this->db_state['dsn']);
      $this->db_state['connected'] = $this->CoreDatabase->open($this->AdministratorAgent, $DbUrl);
    }
        
    //
    // Stop loading if there is no connection to the core database.
    //
    $this->ServerEnvironment->setVariable(
      $this->AdministratorAgent, 
      AblePolecat_Environment_Server::SYSVAR_CORE_DATABASE,
      $this->db_state
    );
    if ($this->db_state['connected']) {
      //
      // Start logging to database.
      //
      $this->Log = AblePolecat_Log_Pdo::wakeup($this);
      AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'Connection to core database established.');
    }
    else {
      AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'Connection to core database is not established.');
    }
    
    $this->UserAgent = NULL;
    
    AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'Server mode is initialized.');
  }
}