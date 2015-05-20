<?php
/**
 * @file      polecat/core/Database/Pdo.php
 * @brief     Encapsulates a PDO database connection.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */
 
require_once(ABLE_POLECAT_CORE. DIRECTORY_SEPARATOR . 'Database.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Statement', 'Sql.php')));

interface AblePolecat_Database_PdoInterface extends AblePolecat_DatabaseInterface {
  
  /**
   * Returns an SQLSTATE, a five characters alphanumeric identifier defined in ANSI SQL-92 standard.
   *
   * @return mixed SQLSTATE or NULL.
   */
  public function getErrorCode();
  
  /**
   * Returns error information about last operation performed by this database handle.
   *
   * @return mixed Array of error information or NULL.
   */
  public function getErrorInfo();
  
  /**
   * @return mixed ID of the last inserted row or NULL.
   */
  public function getLastInsertId();
  
  /**
   * Prepares a SQL statement for execution.
   *
   * @param string $statement SQL statement to prepare.
   * @param array $driver_options
   *
   * @return PDOStatement The prepared statement or NULL.
   */
  public function prepareStatement($statement, $driver_options = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
  
  /**
   * Places quotes around and escapes special characters within input string (if required).
   * 
   * @param string $input.
   *
   * @return mixed Quoted string that is theoretically safe to pass into an SQL statement or FALSE.
   */
  public function quote($input);
}

class AblePolecat_Database_Pdo extends AblePolecat_DatabaseAbstract implements AblePolecat_Database_PdoInterface {
    
  /**
   * @var error information.
   */
  private $error_info;
  
  /**
   * @var string Unique id of pooled database connection (database-name.user-name).
   */
  private $id;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * System unique ID.
   *
   * @return scalar Subject unique identifier.
   */
  public function getId() {
    return $this->id;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Save database connection.
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
   * @return AblePolecat_Database_Pdo or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    $Database = NULL;

    if (isset($Subject)) {
      $dbClientRole = $Subject->getActiveRole(AblePolecat_AccessControl_Role_Client_Database::ROLE_ID);
      if ($dbClientRole) {
        //
        // Attempt to open database connection.
        //
        $Database = new AblePolecat_Database_Pdo($Subject);
        $Database->open($Subject);
      }
      else {
        throw new AblePolecat_AccessControl_Exception(sprintf("%s is not authorized for %s role.",
          $Subject->getName(),
          AblePolecat_AccessControl_Role_Client_Database::ROLE_NAME
        ));
      }
    }
    else {
      $Database = new AblePolecat_Database_Pdo();
    }
    return $Database;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_DatabaseInterface.
   ********************************************************************************/
    
  /**
   * Opens an existing resource or makes an empty one accessible depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking access.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Existing or new resource.
   * @param string $name Optional common name for new resources.
   *
   * @return bool TRUE if access to resource is granted, otherwise FALSE.
   */
  public function open(AblePolecat_AccessControl_AgentInterface $Agent = NULL, AblePolecat_AccessControl_Resource_LocaterInterface $Url = NULL) {
    
    $open = FALSE;
    
    $dsn = NULL;
    $dbUser = NULL;
    $dbPass = NULL;
    if (isset($Agent)) {
      $dbClientRole = $Agent->getActiveRole(AblePolecat_AccessControl_Role_Client_Database::ROLE_ID);
      if ($dbClientRole) {
        $Url = $dbClientRole->getResourceLocater();
        $AccessControlToken = $dbClientRole->getAccessControlToken();
        if (isset($Url) && isset($AccessControlToken)) {
          $dsn = $Url->getDsn();
          $dbUser = $AccessControlToken->getUsername();
          $dbPass = $AccessControlToken->getPassword();
        }
      }
      else {
        throw new AblePolecat_AccessControl_Exception(sprintf("%s is not authorized for %s role.",
          $Agent->getName(),
          AblePolecat_AccessControl_Role_Client_Database::ROLE_NAME
        ));
      }
    }
    else {
      if (isset($Url) && is_a($Url, 'AblePolecat_AccessControl_Resource_Locater_DsnInterface')) {
        $dsn = $Url->getDsn();
        $dbUser = $Url->getUsername();
        $dbPass = $Url->getPassword();
      }
    }
    if (isset($dsn) && isset($dbUser) && isset($dbPass)) {
      //
      // This prevents users from establishing a connection to an unsecured MySql server
      // on a local computer without specifying a database, user or password (as would be
      // the case if setting up a local WAMP/MAMP environment with default settings).
      //
      if (($dbUser === '') || ($dbPass === '')) {
        $this->error_info[] = sprintf("Access denied. Able Polecat database connection requires user with password. user='%s' password='%s' given.",
          $dbUser,
          $dbPass
        );
      }
      try {
        //
        // Persistent connections are not closed at the end of the script, 
        // but are cached and re-used when another script requests a 
        // connection using the same credentials.
        //
        // $options = NULL;
        // if (is_a($Agent, 'AblePolecat_AccessControl_Agent_User_System')) {
          // $options = array(PDO::ATTR_PERSISTENT => true);
        // }
        $options = array(PDO::ATTR_PERSISTENT => true);
        $DatabaseConnection = new PDO($dsn, $dbUser, $dbPass, $options);
        
        //
        // Save locater. This will also set name.
        //
        $this->setLocater($Url);
        
        //
        // Set unique id for pooled database connection.
        //
        $this->id = sprintf("%s.%s", $this->getName(), $dbUser);
        
        //
        // Pool connection.
        //
        $this->setDatabaseConnection($DatabaseConnection);
        
        //
        // Set open flag.
        //
        $open = TRUE;
      } 
      catch (PDOException $Exception) {
        $this->error_info[] = $Exception->getMessage();
      }
    }
    return $open;
  }
  
  /**
   * Install database objects for given schema.
   *
   * @param AblePolecat_Database_SchemaInterface $Schema
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public function install(AblePolecat_Database_SchemaInterface $Schema) {
    
    $Results = array();
    
    //
    // Install tables.
    //
    $tableDdl = $Schema->getTableDefinitions();
    foreach ($tableDdl as $key => $ddlStatement) {
      $Results[] = $this->executeStatement($ddlStatement, 'CREATE TABLE');
    }
  }
  
  /**
   * Execute SQL DML and return number of rows effected.
   * 
   * NOTE: USE execute() for INSERT, DELETE, UPDATE, REPLACE.
   *       USE query() for SELECT.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql.
   *
   * @return Array Results/rowset.
   * @see query()
   */
  public function execute(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql) {
    
    $Results = array();
    
    switch ($sql->getDmlOp()) {
      default:
        $Results = $this->executeStatement($sql->__toString(), $sql->getDmlOp());
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::SELECT:
        $message = 'execute() method cannot be used to process ' . $sql->getDmlOp() . ' statements.';
        $Results['errorInfo'] = $message;
        $this->error_info[] = $message;
        throw new AblePolecat_Database_Exception($message);
        break;
    }
    
    return $Results;
  }
  
  /**
   * Execute SQL DML and return results as an array.
   * 
   * NOTE: USE query() for SELECT.
   *       USE execute() for INSERT, DELETE, UPDATE, REPLACE.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql.
   *
   * @return Array Results/rowset.
   * @see execute()
   */
  public function query(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql) {
    
    $Results = array();
    
    $DatabaseConnection = $this->getDatabaseConnection();
    if (isset($DatabaseConnection)) {
      switch ($sql->getDmlOp()) {
        default:
          $message = 'query() method cannot be used to process ' . $sql->getDmlOp() . ' statements.';
          $this->error_info[] = $message;
          throw new AblePolecat_Database_Exception($message);
          break;
        case AblePolecat_QueryLanguage_Statement_Sql_Interface::SELECT:
          $PreparedStatement = $DatabaseConnection->prepare($sql);
          if($PreparedStatement->execute()) {
            $Results = $PreparedStatement->fetchAll(PDO::FETCH_ASSOC);
          }
          else {
            $this->error_info[] = $PreparedStatement->errorInfo();
          }
          break;
      }
    }
    else {
      $message = 'Cannot execute SQL. No database connection is available.';
      $Results['errorInfo'] = $message;
      $this->error_info[] = $message;
      throw new AblePolecat_Database_Exception($message);
    }
    return $Results;
  }
  
  /**
   * Indicates whether database connection is established and accessible.
   *
   * @return boolean TRUE if database connection is functional, otherwise FALSE.
   */
  public function ready() {
    $DatabaseConnection = $this->getDatabaseConnection();
    return isset($DatabaseConnection);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Database_PdoInterface.
   ********************************************************************************/
   
  /**
   * Returns an SQLSTATE, a five characters alphanumeric identifier defined in ANSI SQL-92 standard.
   *
   * @return mixed SQLSTATE or NULL.
   */
  public function getErrorCode() {
    
    $code = NULL;
    
    $DatabaseConnection = $this->getDatabaseConnection();
    if (isset($DatabaseConnection)) {
      $code = $DatabaseConnection->errorCode();
    }
    return $code;
  }
  
  /**
   * Returns error information about last operation performed by this database handle.
   *
   * @return mixed Array of error information or NULL.
   */
  public function getErrorInfo() {
    
    $info = NULL;
    
    $DatabaseConnection = $this->getDatabaseConnection();
    if (isset($DatabaseConnection)) {
      $info = $DatabaseConnection->errorInfo();
    }
    return $info;
  }
  
  /**
   * @return mixed ID of the last inserted row or NULL.
   */
  public function getLastInsertId() {
    
    $id = NULL;
    try {
      $DatabaseConnection = $this->getDatabaseConnection();
      if (isset($DatabaseConnection)) {
        $id = $DatabaseConnection->lastInsertId();
      }
    }
    catch (PDOException $Exception) {
      throw new AblePolecat_Database_Exception(__METHOD__ . ' failed: ' . $e->getMessage());
    }
    return $id;
  }
  
  /**
   * Prepares a SQL statement for execution.
   *
   * @param string $statement SQL statement to prepare.
   * @param array $driver_options
   *
   * @return PDOStatement The prepared statement or NULL.
   */
  public function prepareStatement($statement, $driver_options = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)) {
    
    $PreparedStatement = NULL;
    $DatabaseConnection = $this->getDatabaseConnection();
    if (isset($DatabaseConnection)) {
      is_string($statement) ? $sql = $statement : $sql = NULL;
      is_a($statement, 'AblePolecat_QueryLanguage_StatementInterface') ? $sql = $statement->__toString() : NULL;
      $PreparedStatement = $DatabaseConnection->prepare($sql, $driver_options);
    }
    else {
      throw new AblePolecat_Database_Exception('No database connection.', AblePolecat_Error::DB_NO_CONNECTION);
    }
    return $PreparedStatement;
  }
  
  /**
   * Places quotes around and escapes special characters within input string (if required).
   * 
   * @param string $input.
   *
   * @return mixed Quoted string that is theoretically safe to pass into an SQL statement or FALSE.
   */
  public function quote($input) {
    $DatabaseConnection = $this->getDatabaseConnection();
    isset($DatabaseConnection) ? $output = $DatabaseConnection->quote($input) : $output = FALSE;
    return $output;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Return information about user privileges on encapsulated database server.
   *
   * On success, will return something similar to:
   * array['*' => 
   *  array[
   *    '*' => 
   *      array[
   *        'SELECT' => int 0
   *        'INSERT' => int 1
   *        'UPDATE' => int 2
   *        'DELETE' => int 3
   *        'CREATE' => int 4
   *        'DROP' => int 5
   *        'INDEX' => int 6
   *        'ALTER' => int 7
   *        'CREATE TEMPORARY TABLES' => int 8
   *        'LOCK TABLES' => int 9],
   * ],
   * 'polecat' => 
   *   array[
   *     '*' => 
   *       array[
   *         'ALL PRIVILEGES' => int 0]
   *   ],
   * ]
   *
   * @param string $user
   * @param string $password
   * @param string $database
   * @param string host
   *
   * @return Array User grants on database server or NULL if connection failed.
   */
  public function showGrants($user, $password, $database = NULL, $host = 'localhost') {
    
    $privileges = NULL;
    
    try {
      //
      // dsn = mysql://user:pass@localhost/database
      //
      $dsn = sprintf("mysql://%s:%s@%s", $user, $password, $host);
      isset($database) ? $dsn .= "/$database" : NULL;
      $DatabaseConnection = new PDO($dsn, $user, $password);
      $PreparedStatement = $DatabaseConnection->prepare("SHOW GRANTS FOR CURRENT_USER()");
      if($PreparedStatement->execute()) {
        $grants = $PreparedStatement->fetchAll(PDO::FETCH_COLUMN);
        if ($grants) {
          $privileges = array();
          foreach($grants as $recordNumber => $Record) {
            $pos = strpos($Record, 'ON');
            $rightSub = substr($Record, $pos, strlen($Record));
            $leftSub = substr($Record, 0, $pos);
            $pos = strpos($rightSub, 'TO');
            $grantsOn = explode('.', trim(str_replace(array('ON', '`'), '', substr($rightSub, 0, $pos))));
            isset($grantsOn[0]) ? $database = $grantsOn[0] : $database = NULL;
            isset($grantsOn[1]) ? $dbObject = $grantsOn[1] : $dbObject = NULL;
            if (!isset($privileges[$database])) {
              $privileges[$database] = array();
            }
            if (!isset($privileges[$database][$dbObject])) {
              $privileges[$database][$dbObject] = array();
            }
            
            $leftSub = trim(str_replace('GRANT', '', $leftSub));
            $leftSub = explode(', ', $leftSub);
            foreach($leftSub as $key => $grant) {
              $privileges[$database][$dbObject][$grant] = $key;
            }
          }
        }
      }
      else {
        $this->error_info[] = $PreparedStatement->errorInfo();
      }
    } 
    catch (PDOException $Exception) {
      $this->error_info[] = $Exception->getMessage();
    }
    return $privileges;
  }
  
  /**
   * Helper function outputs PDO error information array as string.
   *
   * @param Array $error_info PDO error information.
   *
   * @return string.
   */
  public static function getErrorMessage($error_info) {
    
    $error_msg = '';
    if (isset($error_info)) {
      if (is_array($error_info)) {
        isset($error_info[0]) ? $sqlstate = $error_info[0] : $sqlstate = 0;
        isset($error_info[1]) ? $errorCode = $error_info[1] : $errorCode = 0;
        isset($error_info[2]) ? $errorMsg = $error_info[2] : $errorMsg = '';
        $error_msg = sprintf("SQLSTATE %d MySql error %d %s", $sqlstate, $errorCode, $errorMsg);
      }
      else if (is_scalar($error_info)) {
        isset($error_info[0]) ? $sqlstate = $error_info[0] : $sqlstate = 0;
        isset($error_info[1]) ? $errorCode = $error_info[1] : $errorCode = 0;
        isset($error_info[2]) ? $errorMsg = $error_info[2] : $errorMsg = '';
        $error_msg = strval($error_info);
      }
    }
    return $error_msg;
  }
  
  /**
   * Return an reset internal error cache.
   *
   * @return array.
   */
  public function flushErrors() {
    $error_info = $this->error_info;
    $this->error_info = array();
    return $error_info;
  }
  
  /**
   * Helper function returns a count of 'significant' database errors.
   *
   * NOTE: PDO::errorInfo will return blank error info arrays on some successful DML
   * operations Array(0 => '00000', 1 => null, 2 => null). This function peeks ahead
   * at the internal error store and returns a count of only those errors which are 
   * likely to indicate a failed operation or database server internal error.
   *
   * @return int
   */
  public function getErrorCount() {
    
    $error_count = 0;
    
    foreach($this->error_info as $key => $info) {
      if (isset($info[0])){
        switch ($info[0]) {
          default:
            $error_count += 1;
            break;
          case '00000':
            //
            // exclude from count
            //
            break;
        }
      }
    }
    return $error_count;
  }
  
  /**
   * Writes error information about last operation performed by this database handle to log.
   */
  public function logErrorInfo() {
    $DatabaseConnection = $this->getDatabaseConnection();
    if (isset($DatabaseConnection)) {
      $info = $DatabaseConnection->errorInfo();
      foreach($info as $key => $value) {
        throw new AblePolecat_Database_Exception(strval($value));
      }
    }
  }
  
  /**
   * Execute SQL DML or DDL passed as string.
   *
   * @param string $sql DML or DDL statement.
   * @param string $op  DML or DDL command.
   *
   * @return Array Results/rowset.
   */
  protected function executeStatement($sql, $op) {
    
    //
    // @todo: sub-classes can pass type unsafe parameters.
    //
    $Results = array();
    
    $DatabaseConnection = $this->getDatabaseConnection();
    if (isset($DatabaseConnection)) {
      $RecordCount = $DatabaseConnection->exec($sql);
      if (!$RecordCount) {
        $Results['recordsEffected'] = 0;
        $Results['errorInfo'] = $DatabaseConnection->errorInfo();
        $this->error_info[] = $DatabaseConnection->errorInfo();
      }
      else {
        $Results['recordsEffected'] = $RecordCount;
        if (AblePolecat_QueryLanguage_Statement_Sql_Interface::INSERT == $op) {
          $lastInsertId = $DatabaseConnection->lastInsertId();
          $Results['lastInsertId'] = $lastInsertId;
        }
      }
    }
    else {
      $message = 'Cannot execute SQL. No database connection is available.';
      $Results['errorInfo'] = $message;
      $this->error_info[] = $message;
      throw new AblePolecat_Database_Exception($message);
    }
    return $Results;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->error_info = array();
    $this->id = NULL;
  }
}