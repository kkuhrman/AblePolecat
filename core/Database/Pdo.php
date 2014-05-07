<?php
/**
 * @file      polecat/core/Database/Pdo.php
 * @brief     Encapsulates a PDO database connection.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
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
  
  const UUID              = '503bd610-6431-11e2-bcfd-0800200c9a66';
  const NAME              = 'Able Polecat PDO database';
  
  /**
   * @var AblePolecat_Database_Pdo Instance of singleton.
   */
  protected static $Database = NULL;
  
  /**
   * @var resource Database connection.
   */
  private $DatabaseConnection;
  
  /**
   * @var error information.
   */
  private $error_info;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for security resource.
   *
   * @return string Resource identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security resource.
   *
   * @return string Resource name.
   */
  public static function getName() {
    return self::NAME;
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
    //
    // @todo: save db connection
    //
    if (isset($this->DatabaseConnection)) {
      //
      // @todo: save resource and locater.
      //
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
    
    $Database = NULL;
    
    if (isset($Subject) && is_a($Subject, 'AblePolecat_Mode_Server')) {
      if (!isset(self::$Database)) {
        self::$Database = new AblePolecat_Database_Pdo();
      }
      $Database = self::$Database;
    }
    else {
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
   * @param AblePolecat_AccessControl_Resource_Locater_DsnInterface $Url Existing or new resource.
   * @param string $name Optional common name for new resources.
   *
   * @return bool TRUE if access to resource is granted, otherwise FALSE.
   */
  public function open(AblePolecat_AccessControl_AgentInterface $Agent = NULL, AblePolecat_AccessControl_Resource_Locater_DsnInterface $Url = NULL) {
    
    $open = FALSE;
    
    //
    // @todo: access control
    //
    if (isset($Url)) {
      $dsn = $Url->getDsn();
      $dbUser = $Url->getUsername();
      $dbPass = $Url->getPassword();
      
      //
      // This prevents users from establishing a connection to an unsecured MySql server
      // on a local computer without specifying a database, user or password (as would be
      // the case if setting up a local WAMP/MAMP environment with default settings).
      //
      if (($dbUser === '') || ($dbPass === '')) {
        $this->error_info[] = sprintf("Access denied. Able Tabby database connection requires user with password. user='%s' password='%s' given.",
          $dbUser,
          $dbPass
        );
        $this->Connection = NULL;
      }
      else {
        try {
          $this->DatabaseConnection = new PDO($dsn, $dbUser, $dbPass);
          $this->setLocater($Url);
        } 
        catch (PDOException $Exception) {
          $this->error_info[] = $Exception->getMessage();
          $this->Connection = NULL;
          throw new AblePolecat_Database_Exception('DatabaseConnection failed: ' . $Exception->getMessage());
        }
      }
    }
    $open = isset($this->DatabaseConnection);

    return $open;
  }
  
  /**
   * Execute SQL DML and return number of rows effected.
   * 
   * NOTE: USE execute() for INSERT, DELETE, UPDATE, REPLACE.
   *       USE query() for SELECT.
   *
   * @param string $sql SQL DML statement.
   *
   * @return int Number of rows effected by operation.
   * @see query()
   */
  public function execute($sql) {
    
    $Results = 0;
    
    if (isset($this->DatabaseConnection)) {
      $Results = $this->DatabaseConnection->exec($sql);
      if (!$Results) {
        $this->error_info[] = $this->DatabaseConnection->errorInfo();
      }
    }
    else {
      $this->error_info[] = 'Cannot execute SQL. No database connection is available.';
    }
    return $Results;
  }
  
  /**
   * Execute SQL DML and return results as an array.
   * 
   * NOTE: USE query() for SELECT.
   *       USE execute() for INSERT, DELETE, UPDATE, REPLACE.
   *
   * @param string $sql SQL DML statement.
   *
   * @return int Number of rows effected by operation.
   * @see execute()
   */
  public function query($sql) {
    
    $Results = array();
    
    if (isset($this->DatabaseConnection)) {
      $PreparedStatement = $this->DatabaseConnection->prepare($sql);
      if($PreparedStatement->execute()) {
        $Results = $PreparedStatement->fetchAll(PDO::FETCH_ASSOC);
      }
      else {
        $this->error_info[] = $PreparedStatement->errorInfo();
      }
    }
    else {
      $this->error_info[] = 'Cannot execute SQL. No database connection is available.';
    }
    return $Results;
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
    
    if (isset($this->DatabaseConnection)) {
      $code = $this->DatabaseConnection->errorCode();
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
    
    if (isset($this->DatabaseConnection)) {
      $info = $this->DatabaseConnection->errorInfo();
    }
    return $info;
  }
  
  /**
   * @return mixed ID of the last inserted row or NULL.
   */
  public function getLastInsertId() {
    
    $id = NULL;
    try {
      if (isset($this->DatabaseConnection)) {
        $id = $this->DatabaseConnection->lastInsertId();
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
    if (isset($this->DatabaseConnection)) {
      is_string($statement) ? $sql = $statement : $sql = NULL;
      is_a($statement, 'AblePolecat_QueryLanguage_StatementInterface') ? $sql = $statement->__toString() : NULL;
      $PreparedStatement = $this->DatabaseConnection->prepare($sql, $driver_options);
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
    
    isset($this->DatabaseConnection) ? $output = $this->DatabaseConnection->quote($input) : $output = FALSE;
    return $output;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
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
    if (isset($this->DatabaseConnection)) {
      $info = $this->DatabaseConnection->errorInfo();
      foreach($info as $key => $value) {
        throw new AblePolecat_Database_Exception(strval($value));
      }
    }
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->error_info = array();
    $this->DatabaseConnection = NULL;
  }
}