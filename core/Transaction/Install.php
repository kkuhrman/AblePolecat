<?php
/**
 * @file      polecat/core/Transaction/Install.php
 * @brief     Encloses install procedures within a transaction.
 *
 * Because the install procedures involve creating, altering or dropping the
 * server database, it is one of the few objects in Able Polecat, which makes use
 * of the $_SESSION global variable.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Transaction.php');

class AblePolecat_Transaction_Install extends AblePolecat_TransactionAbstract {
  
  /**
   * Constants.
   */
  const UUID = '9e0398f2-604b-11e4-8bab-0050569e00a2';
  const NAME = 'install transaction';

  const ARG_USER = 'user';
  const ARG_PASS = 'pass';
  const ARG_AUTH = 'authority';
  
  /**
   * @var AblePolecat_AccessControl_Agent_User Instance of singleton.
   */
  private static $Transaction;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for agent.
   *
   * @return string Transaction identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for agent.
   *
   * @return string Transaction name.
   */
  public static function getName() {
    return self::NAME;
  }
  
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
    if (!isset(self::$Transaction)) {
      //
      // Unmarshall (from numeric keyed index to named properties) variable args list.
      //
      $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
      self::$Transaction = new AblePolecat_Transaction_Install($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
      self::prepare(self::$Transaction, $ArgsList, __FUNCTION__);
    }
    return self::$Transaction;
  }
  
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
          AblePolecat_Host::setSessionVariable($this, AblePolecat_Host::POLECAT_INSTALL_TRX, $transactionId);
          AblePolecat_Host::setSessionVariable($this, AblePolecat_Host::POLECAT_INSTALL_SAVEPT, 'start');
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
    
    $resourceClassName = $this->getResourceRegistration()->getResourceClassName();
    if (isset($resourceClassName) && ($resourceClassName === 'AblePolecat_Resource_Restricted_Install')) {
      switch ($this->getRequest()->getMethod()) {
        default:
          break;
        case 'GET':
          //
          // Resource request resolves to registered class name, try to load.
          // Attempt to load resource class
          //
          try {
            // $Resource = AblePolecat_Resource_Restricted_Install::wakeup(AblePolecat_AccessControl_Agent_User::wakeup());
            $Resource = AblePolecat_Resource_Core_Factory::wakeup(
              $this->getDefaultCommandInvoker(),
              'AblePolecat_Resource_Restricted_Install'
            );
            $this->setStatus(self::TX_STATE_COMPLETED);
          }
          catch(AblePolecat_AccessControl_Exception $Exception) {
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
                $Resource = AblePolecat_Resource_Core_Factory::wakeup(
                  $this->getAgent(),
                  'AblePolecat_Resource_Core_Form'
                );
                $Resource->addText('Enter name and password for user authorized to create database.');
                $Resource->addControl('label', array('for' => 'userName'), 'Username: ');
                $Resource->addControl('input', array('id' => 'userName', 'type' => 'text', 'name' => self::ARG_USER));
                $Resource->addControl('label', array('for' => 'passWord'), 'Password: ');
                $Resource->addControl('input', array('id' => 'passWord', 'type' => 'password', 'name' => self::ARG_PASS));
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
          }
          break;
        case 'POST':
          break;
      }
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