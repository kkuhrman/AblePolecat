<?php
/**
 * @file      polecat/core/Transaction/Agent.php
 * @brief     Base class for transactions effecting Agent (e.g. authentication).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Transaction.php');

abstract class AblePolecat_Transaction_AgentAbstract extends AblePolecat_TransactionAbstract {
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
   
  /**
   * Commit
   */
  public function commit() {
    //
    // Parent updates transaction in database.
    //
    parent::commit();
  }
}