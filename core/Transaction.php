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
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Transaction.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Overloadable.php');

interface AblePolecat_TransactionInterface extends AblePolecat_CacheObjectInterface, AblePolecat_OverloadableInterface {
  
  /**
   * Transaction states.
   */
  const TX_STATE_COMPLETED      = 'COMPLETED'; // done but not committed.
  const TX_STATE_COMMITTED      = 'COMMITTED'; // done and flushed
  const TX_STATE_PENDING        = 'PENDING';   // in progress
  // const TX_STATE_INCOMPLETE     = 'INCOMPLETE';
  const TX_STATE_ABORTED        = 'ABORTED';   // incomplete, to be discarded
  
  /**
   * Overloaded function arguments.
   */
  const TX_ARG_SUBJECT          = 'Subject';
  const TX_ARG_AGENT            = 'Agent';
  const TX_ARG_TRANSACTION_ID   = 'transactionId';
  const TX_ARG_SAVEPOINT_ID     = 'savepointId';
  const TX_ARG_REQUEST          = 'Request';
  const TX_ARG_RESOURCE_REG     = 'ResourceRegistration';
  const TX_ARG_PARENT           = 'Parent';
  
  /**
   * Save point at the very beginning of transaction.
   */
  const SAVEPOINT_NAME_START    = 'start';
  
  /**
   * Commit
   */
  public function commit();
  
  /**
   * @return AblePolecat_AccessControl_AgentInterface or NULL.
   */
  public function getAgent();
  
  /**
   * @return AblePolecat_TransactionInterface.
   */
  public function getParent();
  
  /**
   * @return AblePolecat_Message_RequestInterface.
   */
  public function getRequest();
  
  /**
   * @return AblePolecat_Registry_Entry_ResourceInterface.
   */
  public function getResourceRegistration();
  
  /**
   * @return string ID of current transaction save point.
   */
  public function getSavepointId();
  
  /**
   * @return string Status of transaction.
   */
  public function getStatus();
  
  /**
   * @return string HTTP status code.
   */
  public function getStatusCode();
  
  /**
   * @return string ID of current transaction.
   */
  public function getTransactionId();
  
  /**
   * Rollback
   */
  public function rollback();
  
  /**
   * Begin or resume the transaction.
   *
   * @return AblePolecat_ResourceInterface The result of the work, partial or completed.
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function start();
}

abstract class AblePolecat_TransactionAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_TransactionInterface {
  
  /**
   * @var AblePolecat_AccessControl_AgentInterface.
   */
  private $Agent;
  
  /**
   * @var AblePolecat_Registry_Class Class Registry.
   */
  private $ClassRegistry;
  
  /**
   * @var AblePolecat_TransactionInterface.
   */
  private $Parent;
  
  /**
   * @var AblePolecat_Message_RequestInterface.
   */
  private $Request;
    
  /**
   * @var AblePolecat_Registry_Entry_ResourceInterface.
   */
  private $ResourceRegistration;
  
  /**
   * @var string ID of current transaction save point.
   */
  private $savepointId;
  
  /**
   * @var string Status of transaction.
   */
  private $status;
  
  /**
   * @var string HTTP status code.
   */
  private $statusCode;
  
  /**
   * @var string ID of current transaction.
   */
  private $transactionId;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // If transaction is complete but not committed, commit now.
    //
    if ($this->getStatus() == self::TX_STATE_COMPLETED) {
      $this->commit();
    }
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_OverloadableInterface.
   ********************************************************************************/
  
  /**
   * Marshall numeric-indexed array of variable method arguments.
   *
   * @param string $method_name __METHOD__ will render className::methodName; __FUNCTION__ is probably good enough.
   * @param Array $args Variable list of arguments passed to method (i.e. get_func_args()).
   * @param mixed $options Reserved for future use.
   *
   * @return Array Associative array representing [argument name] => [argument value]
   */
  public static function unmarshallArgsList($method_name, $args, $options = NULL) {
    
    $ArgsList = AblePolecat_ArgsList::create();
    
    foreach($args as $key => $value) {
      switch ($method_name) {
        default:
          break;
        case 'wakeup':
          isset($args[0]) ? $Subject = $args[0] : $Subject = NULL;
          if (isset($Subject) && is_a($Subject, 'AblePolecat_Command_TargetInterface')) {
            $ArgsList->{self::TX_ARG_SUBJECT} = $Subject;
            switch($key) {
              default:
                break;
              case 1:
                $ArgsList->{self::TX_ARG_AGENT} = $value;
                break;
              case 2:
                $ArgsList->{self::TX_ARG_REQUEST} = $value;
                break;
              case 3:
                $ArgsList->{self::TX_ARG_RESOURCE_REG} = $value;
                break;
              case 4:
                $ArgsList->{self::TX_ARG_TRANSACTION_ID} = $value;
                break;
              case 5:
                $ArgsList->{self::TX_ARG_SAVEPOINT_ID} = $value;
                break;
              case 6:
                $ArgsList->{self::TX_ARG_PARENT} = $value;
                break;
            }
          }
          else {
            $error_msg = sprintf("%s is not permitted to start or resume a transaction.", AblePolecat_Data::getDataTypeName($Subject));
            throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
          }          
          break;
      }
    }
    return $ArgsList;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Commit
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
  
  /**
   * @return AblePolecat_AccessControl_AgentInterface or NULL.
   */
  public function getAgent() {
    
    $Agent = NULL;
    if (isset($this->Agent)) {
      $Agent = $this->Agent;
    }
    else {
      throw new AblePolecat_Transaction_Exception(sprintf("Transaction [ID:%s] Agent is null.", $this->getTransactionId()));
    }
    return $Agent;
  }
  
  /**
   * @return AblePolecat_TransactionInterface.
   */
  public function getParent() {
    
    $Parent = NULL;
    if (isset($this->Parent)) {
      $Parent = $this->Parent;
    }
    else {
      throw new AblePolecat_Transaction_Exception(sprintf("Transaction [ID:%s] has no parent transaction.", $this->getTransactionId()));
    }
    return $Parent;
  }
  
  /**
   * @return AblePolecat_Message_RequestInterface.
   */
  public function getRequest() {
    $Request = NULL;
    if (isset($this->Request)) {
      $Request = $this->Request;
    }
    else {
      throw new AblePolecat_Transaction_Exception(sprintf("Transaction [ID:%s] Request is null.", $this->getTransactionId()));
    }
    return $Request;
  }
  
  /**
   * @return AblePolecat_Registry_Entry_ResourceInterface.
   */
  public function getResourceRegistration() {
    $ResourceRegistration = NULL;
    if (isset($this->ResourceRegistration)) {
      $ResourceRegistration = $this->ResourceRegistration;
    }
    else {
      throw new AblePolecat_Transaction_Exception(sprintf("Transaction [ID:%s] resource registration is null.", $this->getTransactionId()));
    }
    return $ResourceRegistration;
  }
  
  /**
   * @return string ID of current transaction save point.
   */
  public function getSavepointId() {
    return $this->savepointId;
  }
  
  /**
   * @return string Status of transaction.
   */
  public function getStatus() {
    return $this->status;
  }
  
  /**
   * @return string ID of current transaction.
   */
  public function getTransactionId() {
    return $this->transactionId;
  }
  
  /**
   * @return string HTTP status code.
   */
  public function getStatusCode() {
    return $this->statusCode;
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
    // Update raw request log entry with delegate transaction ID.
    //
    $sql = __SQL()-> 
      update('request')->
      set('transactionId')->
      values($this->getTransactionId())->
      where(sprintf("`requestId` = %d", $this->getRequest()->getRawRequestLogRecordId()));
    $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    
    //
    // check save point
    //
    $savepointId = $this->getSavepointId();
    
    //
    // If save point does not exist, create new save point at start and begin there.
    //
    if (!isset($savepointId)) {
      $this->save(self::SAVEPOINT_NAME_START);
    }
    
    //
    // Otherwise begin at existing save point
    //
    $Resource = $this->run();
    
    return $Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return AblePolecat_Registry_Class.
   */
  protected function getClassRegistry() {
    if (!isset($this->ClassRegistry)) {
      $CommandResult = AblePolecat_Command_GetRegistry::invoke($this->getDefaultCommandInvoker(), 'AblePolecat_Registry_Class');
      if ($CommandResult->success()) {
        //
        // Save reference to class registry.
        //
        $this->ClassRegistry = $CommandResult->value();
      }
      else {
        throw new AblePolecat_AccessControl_Exception("Failed to retrieve class registry.");
      }
    }
    return $this->ClassRegistry;
  }
  
  /**
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   */
  protected function setAgent(AblePolecat_AccessControl_AgentInterface $Agent) {
    $this->Agent = $Agent;
  }
  
  /**
   * @param AblePolecat_TransactionInterface $Parent.
   */
  protected function setParent(AblePolecat_TransactionInterface $Parent = NULL) {
    $this->Parent = $Parent;
  }
  
  /**
   * @param AblePolecat_Message_RequestInterface.
   */
  protected function setRequest(AblePolecat_Message_RequestInterface $Request) {
    $this->Request = $Request;
  }
  
  /**
   * @param AblePolecat_Registry_Entry_ResourceInterface $ResourceRegistration.
   */
  public function setResourceRegistration(AblePolecat_Registry_Entry_ResourceInterface $ResourceRegistration) {
    $this->ResourceRegistration = $ResourceRegistration;
  }
  
  /**
   * @param string $savepointId ID of current transaction save point.
   */
  protected function setSavepointId($savepointId) {
    $this->savepointId = $savepointId;
  }
  
  /**
   * @param string $status Status of transaction.
   */
  protected function setStatus($status) {
    $this->status = $status;
  }
    
  /**
   * @param string HTTP status code.
   */
  protected function setStatusCode($statusCode) {
    $this->statusCode = $statusCode;
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
   * @param string $savepointName Name of save point.
   *
   * @return string ID of new save point.
   * @throw AblePolecat_Transaction_Exception if both save point not inserted and transaction not updated.
   */
  protected function save($savepointName) {
    
    //
    // All DML operations must succeed or all fails.
    //
    $transactionStarted = FALSE;
    $transactionUpdated = FALSE;
    $savepointCreated = FALSE;
    $updateTime = time();
    
    //
    // ID of parent transaction if any.
    //
    $parentTransactionId = NULL;
    try {
      $parentTransactionId = $this->getParent()->getTransactionId();
    }
    catch (AblePolecat_Transaction_Exception $Exception) {
      //
      // ignore and continue
      //
    }
    
    //
    // If transaction ID is not set, start a new transaction.
    //
    $transactionId = $this->getTransactionId();
    $savepointId = $this->getSavepointId();
    if (!isset($transactionId) || ($transactionId == '')) {
      throw new AblePolecat_Transaction_Exception(sprintf("Attempt to create save point [ID:%s] with invalid transaction ID.", $savepointId));
    }
    
    //
    // Create new savepoint ID.
    //
    $savepointId = uniqid();
    $this->setSavepointId($savepointId);
    
    switch ($savepointName) {
      default:
        //
        // Update record of transaction.
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
        $transactionStarted = $transactionUpdated;
        break;
      case self::SAVEPOINT_NAME_START:
        //
        // Create record of transaction.
        //
        $sessionNumber = AblePolecat_Host::getSessionNumber();
        $sql = __SQL()->          
          insert(
            'transactionId',
            'sessionNumber',
            'requestMethod',
            'resourceId',
            'createTime', 
            'updateTime', 
            'savepointId',
            'parentTransactionId')->
          into('transaction')->
          values(
            $transactionId, 
            $sessionNumber,
            $this->getRequest()->getMethod(),
            $this->getResourceRegistration()->getResourceId(),
            $updateTime,
            $updateTime,
            $savepointId,
            $parentTransactionId
        );
        $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
        $transactionStarted = $CommandResult->success();
        $transactionUpdated = $transactionStarted;
        break;
    }
    
    //
    // Create record of save point.
    //
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
        $savepointName
    );
    $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    $savepointCreated = $CommandResult->success();
    
    if (($transactionStarted == FALSE) || ($transactionUpdated == FALSE) || ($savepointCreated == FALSE)) {
      throw new AblePolecat_Transaction_Exception(sprintf("Failed to create save point given by $savepointName on transaction %s [ID:%s]",
          $savepointName, $this->getTransactionId()), AblePolecat_Transaction_Exception::CODE_DATABASE_ERROR
      );
    }
    return $savepointId;
  }
  
  /**
   * Load requested transaction and prepare to run as child of transaction in progress.
   *
   * Able Polecat uses the phrase 'enlist transaction' to mean:
   * 1. Load child transaction class.
   * 2. Prepare child transaction to run.
   * 3. Push on top of current transaction stack.
   *
   * @param string $transactionClassName Name of transaction class.
   * @param AblePolecat_Message_RequestInterface $Message Optional request to enlisted transaction.
   * @param AblePolecat_Registry_Entry_ResourceInterface $ResourceRegistration Optional, accompanies $Message.
   *
   * @return Instance of AblePolecat_TransactionInterface ready to run.
   * @throw AblePolecat_Transaction_Exception if transaction could not be loaded, prepared or enlisted.
   */
  protected function enlistTransaction(
    $transactionClassName,
    AblePolecat_Message_RequestInterface $Message = NULL,
    AblePolecat_Registry_Entry_ResourceInterface $ResourceRegistration = NULL
  ) {
    
    $Transaction = NULL;
    
    //
    // Verify requested class implements AblePolecat_TransactionInterface.
    //
    $ClassRegistration = $this->getClassRegistry()->isLoadable($transactionClassName);
    if ($ClassRegistration) {
      if (!is_subclass_of($transactionClassName, 'AblePolecat_TransactionInterface')) {
        throw new AblePolecat_Transaction_Exception(
          sprintf("Transaction classes must implement AblePolecat_TransactionInterface. %s does not.",
            $transactionClassName
          )
        );
      }
              
      //
      // Start or resume the transaction
      //
      $Transaction = $this->getClassRegistry()->loadClass(
        $transactionClassName,
        $this->getDefaultCommandInvoker(),
        $this->getAgent(),
        $Message,
        $ResourceRegistration,
        NULL,
        NULL,
        $this
      );
    }
    else {
      throw new AblePolecat_Transaction_Exception(
        sprintf("%s is not a loadable class. Cannot enlist as subordinate transaction.",
          $transactionClassName
        )
      );
    }
    
    return $Transaction;
  }
  
  /**
   * Finalize transaction initiation prior to start.
   *
   * @param AblePolecat_TransactionInterface &$Transaction Reference to transaction to start.
   * @param AblePolecat_ArgsListInterface $ArgsList Unmarshalled arguments to wakeup().
   *
   * @throw AblePolecat_Transaction_Exception if both save point not inserted and transaction not updated.
   */
  protected static function prepare(
    AblePolecat_TransactionInterface &$Transaction = NULL,
    AblePolecat_ArgsListInterface $ArgsList = NULL
  ) {
    
    if (isset($Transaction)) {
      //
      // Unpack rest of arguments list.
      //
      if (isset($ArgsList)) {
        $Transaction->setAgent($ArgsList->getArgumentValue(self::TX_ARG_AGENT));
        $Request = $ArgsList->getArgumentValue(self::TX_ARG_REQUEST);
        isset($Request) ? $Transaction->setRequest($Request) : NULL;
        $ResourceRegistration = $ArgsList->getArgumentValue(self::TX_ARG_RESOURCE_REG);
        isset($ResourceRegistration) ? $Transaction->setResourceRegistration($ResourceRegistration) : NULL;
        $Transaction->setTransactionId($ArgsList->getArgumentValue(self::TX_ARG_TRANSACTION_ID));
        $Transaction->setSavepointId($ArgsList->getArgumentValue(self::TX_ARG_SAVEPOINT_ID));
        $parent = $ArgsList->getArgumentValue(self::TX_ARG_PARENT);
        if ($parent == '') {
          $parent = NULL;
        }
        $Transaction->setParent($parent);
      }
      else {
        throw new AblePolecat_Transaction_Exception("Failed to prepare transaction because constructor arguments are invalid.");
      }
      
      //
      // If transaction ID is not set, create a new one.
      //
      $transactionId = $Transaction->getTransactionId();
      $savepointId = $Transaction->getSavepointId();
      if (!isset($transactionId) || ($transactionId == '')) {
        if (isset($savepointId)) {
          throw new AblePolecat_Transaction_Exception(sprintf("Attempt to restore save point [ID:%s] with invalid transaction ID.", $savepointId));
        }
        //
        // Create new transaction ID.
        //
        $transactionId = uniqid();
        $Transaction->setTransactionId($transactionId);
      }
    }
    else {
      throw new AblePolecat_Transaction_Exception("Failed to prepare transaction because object is not created.");
    }
    return $Transaction;
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override this for initializtion at create time.
   */
  protected function initialize() {
    $this->Agent = NULL;
    $this->ClassRegistry = NULL;
    $this->Parent = NULL;
    $this->Request = NULL;
    $this->transactionId = NULL;
    $this->ResourceRegistration = NULL;
    $this->savepointId = NULL;
    $this->status = self::TX_STATE_PENDING;
    $this->statusCode = 200;
  }
}