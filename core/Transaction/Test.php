<?php
/**
 * @file      polecat/core/Transaction/Test.php
 * @brief     Built-in transaction manages local unit test execution.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction.php')));

class AblePolecat_Transaction_Test extends  AblePolecat_TransactionAbstract {
  
  /**
   * Registry article constants.
   */
  const UUID = '3c421fbe-df90-11e4-b585-0050569e00a2';
  const NAME = 'AblePolecat_Transaction_Test';
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    $Transaction = new AblePolecat_Transaction_Test($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
    self::prepare($Transaction, $ArgsList, __FUNCTION__);
    return $Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Commit
   */
  public function commit() {
  }
  
  /**
   * Rollback
   */
  public function rollback() {
  }
  
  /**
   * Begin or resume the transaction.
   *
   * @return AblePolecat_ResourceInterface The result of the work, partial or completed.
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function start() {
    
    $Resource = NULL;
    
    //
    // Unit tests can only be executed by local host.
    //
    isset($_SERVER['REMOTE_ADDR']) ? $remoteIp = $_SERVER['REMOTE_ADDR'] : $remoteIp = 'unknown';
    if ($remoteIp === '127.0.0.1') {
      //
      // Check request method.
      //
      $method = $this->getRequest()->getMethod();
      switch ($method) {
        default:
          break;
        case 'GET':
          //
          // Select unit test(s).
          //
          $Resource = $this->selectTests();
          break;
        case 'POST':
          //
          // Execute unit test(s) and return results.
          //
          $Resource = $this->executeTests();
          break;
      }
    }
    else {
      $Resource = AblePolecat_Resource_Core_Factory::wakeup(
        $this->getDefaultCommandInvoker(),
        'AblePolecat_Resource_Core_Error',
        'Forbidden',
        sprintf("Unit tests can only be executed by local host. Your address is %s.", $remoteIp)
      );
      $this->setStatusCode(403);
      $this->setStatus(self::TX_STATE_COMPLETED);
    }
    return $Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Return a form for selecting one or more unit tests to run.
   *
   * @return AblePolecat_ResourceInterface The result of the work, partial or completed.
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  protected function selectTests() {
    $Resource = AblePolecat_Resource_Core_Factory::wakeup(
      $this->getAgent(),
      'AblePolecat_Resource_Core_Form'
    );
    $Resource->addText('Enter database name, user name and password for Able Polecat core database.');
    $Resource->addControl('label', array('for' => 'databaseName'), 'Database: ');
    $Resource->addControl('input', array('id' => 'databaseName', 'type' => 'text', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_DB));
    $Resource->addControl('label', array('for' => 'userName'), 'Username: ');
    $Resource->addControl('input', array('id' => 'userName', 'type' => 'text', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_USER));
    $Resource->addControl('label', array('for' => 'passWord'), 'Password: ');
    $Resource->addControl('input', array('id' => 'passWord', 'type' => 'password', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_PASS));
    $Resource->addControl('input', array('type'=>'hidden', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_REDIRECT, 'value' => AblePolecat_Resource_Core_Test::UUID));
    $Resource->addControl('input', array('type'=>'hidden', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_REFERER, 'value' => AblePolecat_Resource_Core_Test::UUID));
    return $Resource;
  }
  
  /**
   * Execute unit test(s) and return results.
   *
   * @return AblePolecat_ResourceInterface The result of the work, partial or completed.
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  protected function executeTests() {
    $Resource = AblePolecat_Resource_Core_Factory::wakeup(
      $this->getAgent(),
      'AblePolecat_Resource_Core_Test'
    );
    return $Resource;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}