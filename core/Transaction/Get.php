<?php
/**
 * @file      polecat/core/Transaction/Get.php
 * @brief     Encapsulates a GET request as a transaction.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
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
    // Parent updates transaction in database.
    //
    parent::commit();
  }
}