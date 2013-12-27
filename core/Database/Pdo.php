<?php
/**
 * @file: Pdo.php
 * Encapsulates a PDO database connection.
 */
 
require_once(ABLE_POLECAT_CORE. DIRECTORY_SEPARATOR . 'Database.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Statement', 'Sql.php')));

class AblePolecat_Database_Pdo extends AblePolecat_DatabaseAbstract implements AblePolecat_DatabaseInterface {
  
  const UUID              = '503bd610-6431-11e2-bcfd-0800200c9a66';
  const NAME              = 'PDO database';
  
  /**
   * @var AblePolecat_Database_Pdo Instance of singleton.
   */
  private static $Database = NULL;
  
  /**
   * @var resource Database connection.
   */
  private $DatabaseConnection;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->DatabaseConnection = NULL;
  }
  
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
   * Opens an existing resource or makes an empty one accessible depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking access.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Existing or new resource.
   * @param string $name Optional common name for new resources.
   *
   * @return bool TRUE if access to resource is granted, otherwise FALSE.
   */
  public function open(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_AccessControl_Resource_LocaterInterface $Url = NULL) {
    
    $open = FALSE;
    
    //
    // @todo: access control
    //
    if (isset($Url)) {
      //
      // @todo str_replace is a hack for cleansing/normalizing db name
      //
      $dsn = sprintf("%s:dbname=%s;host=%s",
        $Url->getProtocol(),
        str_replace('/', '', $Url->getPathname()),
        $Url->getHost());
      $user = $Url->getUsername();
      $password = $Url->getPassword();
      try {
        $this->DatabaseConnection = new PDO($dsn, $user, $password);
        $this->setLocater($Url);
      } catch (PDOException $e) {
        throw new AblePolecat_Database_Exception('Connection failed: ' . $e->getMessage());
      }
    }
    $open = isset($this->DatabaseConnection);

    return $open;
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
}