<?php
/**
 * @file      polecat/core/Transaction.php
 * @brief     Transactions manage session state and user work flow across HTTP requests.
 *
 * Implements AblePolecat_CacheObjectInterface using wakeup() to start or resume a 
 * transaction and sleep() to create a save point.
 * 
 * This object may encapsulate an ACID transaction between two or more service clients or
 * simply manage user work flow and/or session state from one request to another.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Transaction.php')));

interface AblePolecat_TransactionInterface extends AblePolecat_CacheObjectInterface {
  
  /**
   * Commit
   */
  public function commit();
  
  /**
   * Rollback
   */
  public function rollback();
}

abstract class AblePolecat_TransactionAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_TransactionInterface {
  
  /**
   * @var AblePolecat_AccessControl_AgentInterface.
   */
  private $Agent;
  
  /**
   * @var string ID of current transaction.
   */
  private $transactionId;
  
  /**
   * @return AblePolecat_AccessControl_AgentInterface or NULL.
   */
  public function getAgent() {
    return $this->Agent;
  }
  
  /**
   * @return string ID of current transaction.
   */
  public function getTransactionId() {
    return $this->transactionId;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   */
  protected function setAgent(AblePolecat_AccessControl_AgentInterface $Agent) {
    $this->Agent = $Agent;
  }
  
  /**
   * @param string $transactionId ID of current transaction.
   */
  protected function setTransactionId($transactionId) {
    $this->transactionId = $transactionId;
  }
  
  /**
   * Create a save point by given name on existing transaction.
   *
   * Creating a save point will also update the existing transaction. If the 
   * transaction has not already been started, calling save in effect starts it.
   *
   * @param string $name Name of save point.
   *
   * @throw AblePolecat_Transaction_Exception if both save point not inserted and transaction not updated.
   */
  protected function save($name) {
    
    //
    // All DML operations must succeed or all fails.
    //
    $transactionStarted = FALSE;
    $transactionUpdated = FALSE;
    $savepointCreated = FALSE;
    
    //
    // First, verify transaction has been started.
    //
    $sql = __SQL()->
      select(
        'transactionId'
        )->
      from('transaction')->
      where(sprintf("transactionId = '%s'", $this->getTransactionId()));
    $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    if ($CommandResult->success()) {
      $transactionStarted = (count($CommandResult->value()) != 0);
      $transactionUpdated = FALSE;
      $savepointCreated = FALSE;
      
      //
      // create save point
      //
      $savepointId = uniqid();
      $transactionId = $this->getTransactionId();
      $updateTime = time();
      $sql = __SQL()->          
        insert(
          'savepointId',
          'transactionId', 
          'savepointName'
          )->
        into('savepoint')->
        values(
          $savepointId,
          $transactionId, 
          $name
      );
      $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
      if ($CommandResult->success()) {
        //
        // Save point created, continue...
        //
        $savepointCreated = TRUE;
        if ($transactionStarted) {
          //
          // Transaction already started so update
          //
          $sql = __SQL()->
            update('transaction')->
            set(
              'updateTime', 
              'savepointId')->
            values(
              $updateTime,
              $savepointId)->
            where(sprintf("transactionId = '%s'", $this->getTransactionId()));
          $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
          $transactionUpdated = $CommandResult->success();
        }
        else {
          //
          // Transaction must be started
          //
          $sessionId = $this->getAgent()->getSessionId();
          $sql = __SQL()->          
            insert(
              'transactionId', 
              'sessionId', 
              'createTime', 
              'updateTime', 
              'savepointId')->
            into('transaction')->
            values(
              $transactionId, 
              $sessionId,
              $updateTime,
              $updateTime,
              $savepointId
          );
          $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
          $transactionStarted = $CommandResult->success();
          $transactionUpdated = $transactionStarted;
        }
      }
    }
    if (($transactionStarted == FALSE) || ($transactionUpdated == FALSE) || ($savepointCreated == FALSE)) {
      throw new AblePolecat_Transaction_Exception(sprint("failed to create save point given by $name on transaction %s [ID:%s]",
          $name, $this->getTransactionId()
      ));
    }
  }
  
  /**
   * Start a transaction by logging records into [transaction] and [savepoint].
   *
   * @param string $name Name of save point.
   *
   * @throw AblePolecat_Transaction_Exception if both save point not inserted and transaction not updated.
   */
  protected function start($name = __METHOD__) {
    //
    // @todo: what to do with orphaned save points (clean up aborted transactions)
    //
    $this->getAgent()->enterTransaction($this->getTransactionId());
    $this->save($name);
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override this for initializtion at create time.
   */
  protected function initialize() {
    $this->Agent = NULL;
    $this->transactionId = NULL;
  }
}