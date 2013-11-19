<?php
/**
 * @file: Transaction.php
 * Encapsulates an ACID transaction between two or more service clients.
 */

interface AblePolecat_TransactionInterface {
  
  /**
   * Commit
   */
  public function commit();
  
  /**
   * Rollback
   */
  public function rollback();
  
  /**
   * Create a savepoint.
   */
  public function save();
  
  /**
   * Start a new transaction.
   * 
   * @return AblePolecat_TransactionInterface.
   */
  public static function start();
}

abstract class AblePolecat_TransactionAbstract implements AblePolecat_TransactionInterface {
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override this for initializtion at create time.
   */
  protected function initialize() {
  }
  
  /**
   * Call start() to create new transaction.
   */
  final protected function __construct() {
    $this->initialize();
  }
}

/**
 * Exceptions thrown by Able Polecat transactions.
 */
class AblePolecat_Transaction_Exception extends AblePolecat_Exception {
}