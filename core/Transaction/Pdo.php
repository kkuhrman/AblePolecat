<?php
/**
 * @file: Pdo.php
 * Uses a PDO database connection for managing transaction locks,savepoints etc.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Transaction.php');

class AblePolecat_Transaction_Pdo extends AblePolecat_TransactionAbstract {
  
  /**
   * @var resource Database connection.
   */
  private $Database = NULL;
  
  /**
   * @var resource A prepared PDO statement for setting locks on service requests.
   */
  private $InsertLockStatement = NULL;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    //
    // Get connection to the database.
    //
    $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
    if (isset($Environment)) {
      $this->Database = $Environment->getDb();
    }
    if (!isset($this->Database)) {
      throw new AblePolecat_Log_Exception("Failed to connect to Able Polecat database", 
        AblePolecat_Error::DB_CONNECT_FAIL);
    }
    
    //
    // Prepare parameterized PDO statement for inserting log messages.
    //
    $sql = "INSERT locks(service, id, createdbyid) VALUES(:service, :id, :createdbyid)";
    $this->InsertLockStatement = $this->Database->prepareStatement($sql);
  }
  
  /**
   * Sets a lock for the given service.
   *
   * @param string $service The service which is subject to the lock.
   * @param string $id The ID of the object of the service.
   * @param int $createdbyid The ID of the user setting the lock. 
   *
   * @return TRUE if the lock was set, otherwise FALSE.
   */
  protected function setLock($service, $id, $createdbyid = 0) {
    
    $result = FALSE;
    if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_DB_MYSQL)) {
      if (isset($this->InsertLockStatement)) {
        $this->InsertLockStatement->bindParam(':service', $service);
        $this->InsertLockStatement->bindParam(':id', $id);
        $this->InsertLockStatement->bindParam(':createdbyid', $createdbyid);
        $result = $this->InsertLockStatement->execute();
      }
    }
    return $result;
  }
  
  /**
   * Commit
   */
  public function commit() {}
  
  /**
   * Rollback
   */
  public function rollback() {}
  
  /**
   * Create a savepoint.
   */
  public function save() {}
  
  /**
   * Start a new transaction.
   * 
   * @return AblePolecat_TransactionInterface.
   */
  public static function start() {
    $Transaction = new AblePolecat_Transaction_Pdo();
    return $Transaction;
  }
}