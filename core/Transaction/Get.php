<?php
/**
 * @file      polecat/core/Transaction/Get.php
 * @brief     Encapsulates a GET request as a transaction.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Transaction.php');

abstract class AblePolecat_Transaction_GetAbstract extends AblePolecat_TransactionAbstract {
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
   
  /**
   * Commit
   *
   * For GET transactions, there is nothing to persist other than the transaction status.
   */
  public function commit() {
    //
    // Transaction already started so update
    //
    $updateTime = time();
    $sql = __SQL()->
      update('transaction')->
      set(
        'updateTime', 
        'status')->
      values(
        $updateTime,
        self::TX_STATE_COMMITTED)->
      where(sprintf("`transactionId` = '%s'", $this->getTransactionId()));
    $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    if ($CommandResult->success() == FALSE) {
      //
      // @todo:
      //
    }
  }
}