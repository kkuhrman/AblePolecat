<?php
/**
 * @file      polecat/core/Transaction/Restricted.php
 * @brief     Base class for transactions, which return restricted resources.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction.php')));

interface AblePolecat_Transaction_RestrictedInterface extends AblePolecat_TransactionInterface  {
  /**
   * Authentication constants.
   */
  const ARG_DB        = 'database-name';
  const ARG_USER      = 'username';
  const ARG_PASS      = 'password';
  const ARG_AUTH      = 'authority';
  const ARG_REDIRECT  = 'redirect';
  const ARG_REFERER   = 'referer';
  
  /**
   * @return UUID Id of redirect resource on authentication.
   */
  public function getRedirectResourceId();
}

abstract class AblePolecat_Transaction_RestrictedAbstract extends AblePolecat_TransactionAbstract {
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
  
  /**
   * Rollback
   */
  public function rollback() {
    //
    // @todo
    //
  }
  
  /**
   * Begin or resume the transaction.
   *
   * @return AblePolecat_ResourceInterface The result of the work, partial or completed.
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function start() {
    
    //
    // Check request method.
    //
    $method = $this->getRequest()->getMethod();
    switch ($method) {
      default:
        break;
      case 'GET':
        //
        // Check if Able Polecat database exists.
        //
        $activeCoreDatabase = AblePolecat_Mode_Server::getActiveCoreDatabaseName();
        if ($activeCoreDatabase) {
          //
          // Database is active. Allow parent to handle from here.
          //
          return parent::start();
        }
        else {
          //
          // Database is not active. Save transaction in $_SESSION global variable.
          //
          $transactionId = $this->getTransactionId();
          AblePolecat_Mode_Session::setSessionVariable($this->getAgent(), AblePolecat_Host::POLECAT_INSTALL_TRX, $transactionId);
          AblePolecat_Mode_Session::setSessionVariable($this->getAgent(), AblePolecat_Host::POLECAT_INSTALL_SAVEPT, 'start');
        }
        break;
      case 'POST':
        $transactionId = AblePolecat_Mode_Session::getSessionVariable($this->getAgent(), AblePolecat_Host::POLECAT_INSTALL_TRX);
        $savePointId = AblePolecat_Mode_Session::getSessionVariable($this->getAgent(), AblePolecat_Host::POLECAT_INSTALL_SAVEPT);
        break;
    }
    
    return $this->run();
  }
  
  /**
   * Run the install procedures.
   *
   * @return AblePolecat_ResourceInterface
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function run() {
    
    $Resource = NULL;
    
    switch ($this->getRequest()->getMethod()) {
      default:
        break;
      case 'GET':
        switch ($this->getConnectorRegistration()->getAccessDeniedCode()) {
          default:
            break;
          case 401:
            //
            // 401 means user requires authentication before request will be granted.
            //
            // $authorityClassName = $this-getConnectorRegistration()->getAuthorityClassName();
            // if (isset($authorityClassName)) {
              // $ChildTransaction = $this->enlistTransaction(
                // $authorityClassName,
                // $this->getRequest(),
                // $this->getResourceRegistration()
              // );
              // $Resource = $ChildTransaction->run();
            // }
            $Referer = $this->getResourceRegistration()->getResourceId();
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
            $Resource->addControl('input', array('type'=>'hidden', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_REDIRECT, 'value' => $this->getRedirectResourceId()));
            $Resource->addControl('input', array('type'=>'hidden', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_REFERER, 'value' => $Referer));
        }
        if (!isset($Resource)) {
          //
          // Return access denied notification.
          // @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
          // 403 means server will refuses to fulfil request regardless of authentication.
          //
          $Resource = AblePolecat_Resource_Core_Factory::wakeup(
            $this->getDefaultCommandInvoker(),
            'AblePolecat_Resource_Core_Error',
            'Access Denied',
            $Exception->getMessage()
          );
          $this->setStatusCode(403);
          $this->setStatus(self::TX_STATE_COMPLETED);
        }
        break;
    }
    return $Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}