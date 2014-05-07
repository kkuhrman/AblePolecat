<?php
/**
 * @file      polecat/core/Transaction.php
 * @brief     Encapsulates an ACID transaction between two or more service clients.
 *
 * Implements AblePolecat_CacheObjectInterface using wakeup() to start or resume a 
 * transaction and sleep() to create a save point.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
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
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct().
   *
   * Sub-classes should override this for initializtion at create time.
   */
  protected function initialize() {
  }
}