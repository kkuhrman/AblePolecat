<?php
/**
 * @file      polecat/core/Transaction/Pdo.php
 * @brief     Uses a PDO database connection for managing transaction locks,savepoints etc.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Transaction.php');

class AblePolecat_Transaction_Pdo extends AblePolecat_TransactionAbstract {
  
  /**
   * @var resource Database connection.
   */
  private $Database = NULL;
  
  /**
   * @var resource A prepared PDO statement for setting locks on service requests.
   */
  private $InsertLockStatement = NULL;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a save point.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
  
  /**
   * Start a new transaction or resume an existing one.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    $Transaction = new AblePolecat_Transaction_Pdo();
    return $Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Commit
   */
  public function commit() {}
  
  /**
   * Rollback
   */
  public function rollback() {}
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
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
}