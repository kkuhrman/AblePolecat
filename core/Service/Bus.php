<?php
/**
 * @file      polecat/core/Service/Bus.php
 * @brief     Provides a channel for Able Polecat services to communicate with one another.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Static.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode', 'Response.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Cached.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xhtml.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xml.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Initiator.php')));
// require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction.php')));

/**
 * Manages multiple web services initiator connections and routes messages
 * between these and the application in scope.
 */

class AblePolecat_Service_Bus extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_Article_StaticInterface {
  
  const UUID              = '3d50dbb0-715e-11e2-bcfd-0800200c9a66';
  const NAME              = 'Able Polecat Service Bus';
  
  const REQUEST           = 'request';
  const RESPONSE          = 'response';
  
  /**
   * 'Home' resource ID.
   */
  const DEFAULT_RESOURCE_ID = '9f0d8882-887e-460f-ada3-40e8bd8f2372';
  
  /**
   * 'Home' resource ID.
   */
  const NOT_FOUND_RESOURCE_ID = '57d072b0-2879-11e4-8c21-0800200c9a66';
  
  /**
   * @var object Singleton instance
   */
  private static $ServiceBus;
  
  /**
   * @var AblePolecat_Registry_Class Class Registry.
   */
  private $ClassRegistry;
  
  /**
   * @var Array of objects, which implement AblePolecat_Service_InitiatorInterface.
   */
  protected $ServiceInitiators;
  
  /**
   * @todo: message queue
   */
  protected $Messages;
  
  /**
   * @var Array transaction log
   */
  protected $transactions;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for security resource.
   *
   * @return string Resource identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security resource.
   *
   * @return string Resource name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {

  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Service_Bus or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$ServiceBus)) {
      self::$ServiceBus = new AblePolecat_Service_Bus($Subject);      
    }					
    return self::$ServiceBus;
  }
  
  /********************************************************************************
   * Message processing methods.
   ********************************************************************************/
  
  /**
   * Add a message to the queue.
   *
   * Executive summary of the dispatch process
   *
   * 1. Get resource registration entry
   * 2. Check if resource is restricted
   *    2.1 If unrestricted check cache for a timely and relevant response.
   *        2.1.1 If cached response found GOTO 7
   *        2.1.2 If no cached response found GOTO 3
   *    2.2 If restricted check if agent/role has permission to access
   *        2.2.1 If access is permitted GOTO 2.1
   *        2.2.2 If access denied and accessDeniedCode == 403 generate access denied 
   *              response and GOTO 7
   *        2.2.3 If access denied and accessDeniedCode == 401 generate authentication 
   *              form response and GOTO 7
   * 3. Start transaction corresponding to request method/resource
   * 4. Get response registration entry corresponding to transaction status code and 
   *    resource object returned by 3
   * 5. Generate response from data returned by 3 & 4
   * 6. Update cache with response
   * 7. Send response.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent with access to requested service.
   * @param AblePolecat_MessageInterface $Message
   */
  public function dispatch(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_MessageInterface $Message) {
    
    $Response = NULL;
    
    try {
      if (is_a($Message, 'AblePolecat_Message_RequestInterface')) {
        $requestMethod = $Message->getMethod();
        $message = sprintf("%s request dispatched to service bus by %s agent.", 
          $requestMethod,
          $Agent->getName()
        );
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
    
        //
        // Get resource/representation registration info.
        //
        $ResourceRegistration = AblePolecat_Registry_Resource::getRegisteredResource($Message);
        $ConnectorRegistration = AblePolecat_Registry_Connector::getRegisteredResourceConnector(
          $ResourceRegistration->getId(), 
          $requestMethod
        );
        
        //
        // If method is GET and resource is not restricted, check cache first.
        //
        $recache = $Message->getQueryStringFieldValue('recache');
        if (($requestMethod == 'GET') && 
            (200 == $ConnectorRegistration->getAccessDeniedCode()) &&
            !isset($recache)) {
          //
          // Check current cache entry against modified dates for both resource and response objects.
          //
          $ResponseRegistration = AblePolecat_Registry_Response::getRegisteredResponse(
            $ResourceRegistration->getId(), 
            200
          );
          
          $primaryKey = array($ResourceRegistration->getId(), 200);
          $CacheRegistration = AblePolecat_Registry_Entry_Cache::fetch($primaryKey);
          if ($CacheRegistration->getLastModifiedTime()) {
            //
            // Check if resource and/or response have been modified since last cache entry.
            //
            $lastModifiedTimes = array(
              $CacheRegistration->getLastModifiedTime(),
              $ResourceRegistration->getLastModifiedTime(),
              $ResponseRegistration->getLastModifiedTime()
            );
            $mostRecentModifiedTime = AblePolecat_Data_Primitive_Scalar_Integer::max($lastModifiedTimes);
            
            if ($mostRecentModifiedTime == $CacheRegistration->getLastModifiedTime()) {
              //
              // Generate response from cache entry
              //
              $Response = AblePolecat_Message_Response_Cached::create();
              $Response->setCachedResponse($CacheRegistration);
              AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Using cached response.');
            }
          }
        }
        
        if (!isset($Response)) {
          //
          // No cached response was available, generate a new response.
          //
          AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Cached response not available or outdated.');
          //
          // Check for open transactions matching request for given method/resource by same agent.
          //
          $transactionId = NULL;
          $savepointId = NULL;
          $parentTransactionId = NULL;
          $sql = __SQL()->
            select(
              'transactionId', 'savepointId', 'parentTransactionId')->
            from('transaction')->
            where(sprintf("`sessionNumber` = %s AND `resourceId` = '%s' AND `status` != '%s'", 
              AblePolecat_Mode_Session::getSessionNumber(),
              $ResourceRegistration->getId(), 
              AblePolecat_TransactionInterface::TX_STATE_COMMITTED)
            );
          $CommandResult = AblePolecat_Command_Database_Query::invoke($this->getDefaultCommandInvoker(), $sql);
          if ($CommandResult->success() && count($CommandResult->value())) {
            //
            // Resume existing transaction.
            //
            $Records = $CommandResult->value();
            $transactionId = $Records[0]['transactionId'];
            $savepointId = $Records[0]['savepointId'];
            isset($Records[0]['parentTransactionId']) ? $parentTransactionId = $Records[0]['parentTransactionId'] : NULL;
          }
          
          try {
            //
            // Validate registered parent transaction class name.
            //
            $transactionClassId = $ConnectorRegistration->getClassId();
            $transactionClassRegistration = $this->getClassRegistry()->getRegistrationById($transactionClassId);
            if (!isset($transactionClassRegistration)) {
              throw new AblePolecat_Service_Exception(sprintf("No transaction class registered for %s method on resource named %s",
                $requestMethod,
                $ResourceRegistration->getName()
              ));
            }
            
            // 
            // Start or resume the transaction
            // @todo: $parentTransactionId must be parent (object)
            //
            $Transaction = $this->getClassRegistry()->loadClass(
              $transactionClassRegistration->getName(),
              $this->getDefaultCommandInvoker(),
              $Agent,
              $Message,
              $ResourceRegistration,
              $ConnectorRegistration,
              $transactionId,
              $savepointId,
              NULL
            );
            $Resource = $Transaction->start();
            if (!isset($Resource) || !is_a($Resource, 'AblePolecat_ResourceInterface')) {
              throw new AblePolecat_Service_Exception(sprintf("%s failed to return object which implements AblePolecat_ResourceInterface",
                AblePolecat_Data::getDataTypeName($Transaction)
              ));
            }
          }
          catch (AblePolecat_Transaction_Exception $Exception) {
            //
            // Failure in the transaction work-flow. 
            // Able Polecat will attempt to recover as gracefully as possible.
            //
            $Resource = $this->recoverFromTransactionFailure($Exception, $ResourceRegistration);
          }
          $Response = $this->getResponse($ResourceRegistration, $Transaction, $Resource);
        }
      }
      else if (is_a($Message, 'AblePolecat_Message_ResponseInterface')) {
        //
        // @todo: handle response message
        //
      }
      else {
        throw new AblePolecat_Service_Exception(sprintf("Able Polecat refused to dispatch message of type %s", AblePolecat_Data::getDataTypeName($Message)));
      }
    } 
    catch(AblePolecat_AccessControl_Exception $Exception) {
      //
      // @todo: save transaction, prepare to listen for next request...
      //
      // throw new AblePolecat_Service_Exception($Exception->getMessage());
    }
    return $Response;
  }
  
  /**
   * Prepare an HTTP response corresponding to the given resource and status code.
   *
   *
   * UPDATE cache with response entity body if corresponding entry:
   * 1. Does not exist.
   * 2. Modified time is older than:
   *    2.1 Resource registration entry modified time
   *    2.2 Response registration entry modified time
   * @todo: auto update modified times in resource/response registration records if:
   * - PHP class file modified time is newer than registry entry modified time
   * - .tpl file modified time is newer than registry entry modified time
   *
   * @param AblePolecat_Registry_Entry_Resource $ResourceRegistration
   * @param AblePolecat_TransactionInterface $Transaction
   * @param AblePolecat_ResourceInterface $Resource
   * 
   * @return AblePolecat_Message_ResponseInterface
   */
  protected function getResponse(
    AblePolecat_Registry_Entry_Resource $ResourceRegistration,
    AblePolecat_TransactionInterface $Transaction, 
    AblePolecat_ResourceInterface $Resource
  ) {
    
    $Response = NULL;
    
    //
    // Search core database for corresponding response registration.
    //
    $ResponseRegistration = AblePolecat_Registry_Response::getRegisteredResponse($Resource->getId(), $Transaction->getStatusCode());
    AblePolecat_Debug::kill($ResponseRegistration);
    $ResponseClassRegistration = $this->getClassRegistry()->getRegistrationById($ResponseRegistration->getClassId());
    if(isset($ResponseClassRegistration)) {
      //
      // @todo: load response class and set entity body.
      //
      $Response = $this->getClassRegistry()->loadClass($ResponseClassRegistration->getName(), $ResponseRegistration);
      $Response->setEntityBody($Resource);
    }
    
    //
    // Update cache with current response.
    // (Do not cache built-in resource responses e.g. error).
    //
    switch($Response->getResourceId()) {
      default:
        $CacheRegistration = AblePolecat_Registry_Entry_Cache::create();
        $CacheRegistration->resourceId = $Response->getResourceId();
        $CacheRegistration->statusCode = $Response->getStatusCode();
        $CacheRegistration->mimeType = $Response->getMimeType();
        $CacheRegistration->lastModifiedTime = time();
        $CacheRegistration->cacheData = $Response->getEntityBody();
        $CacheRegistration->save();
        break;
      case AblePolecat_Resource_Core_Ack::UUID:
      case AblePolecat_Resource_Core_Error::UUID:
      case AblePolecat_Resource_Core_Form::UUID:
      case AblePolecat_Resource_Restricted_Install::UUID:
      case AblePolecat_Resource_Restricted_Update::UUID:
      case AblePolecat_Resource_Restricted_Util::UUID:
        break;
    }
    
    return $Response;
  }
  
  /**
   * Recover from a thrown AblePolecat_Transaction_Exception
   * 
   * @param AblePolecat_Transaction_Exception $Exception The thrown exception.
   * @param AblePolecat_Registry_Entry_ResourceInterface $ResourceRegistration Registration of the requested resource.
   *
   * @return AblePolecat_ResourceInterface.
   */
  protected function recoverFromTransactionFailure(
    AblePolecat_Transaction_Exception $Exception,
    AblePolecat_Registry_Entry_ResourceInterface $ResourceRegistration
  ) {
    
    $Resource = NULL;
    
    $resourceClassName = $ResourceRegistration->getPropertyValue('resourceClassName');
    switch($resourceClassName) {
      default:
        $Resource = AblePolecat_Resource_Core_Factory::wakeup(
          $this->getDefaultCommandInvoker(),
          'AblePolecat_Resource_Core_Error',
          'Transaction failed',
          $Exception->getMessage()
        );
        break;
      case 'AblePolecat_Resource_Core_Ack':
        //
        // If requested resource is ACK, ignore.
        //
        $Resource = AblePolecat_Resource_Core_Factory::wakeup(
          $this->getDefaultCommandInvoker(),
          $resourceClassName
        );
        break;
    }
    return $Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Pops transaction off top of stack.
   * 
   * @return string $tansactionId ID of given transaction or NULL.
   */
  protected function popTransaction() {
    $transactionId = array_pop($this->transactions);
    return $transactionId;
  }
  
  /**
   * Pushes given transaction on top of stack.
   * 
   * @param string $tansactionId ID of given transaction.
   *
   * @return int Number of transactions on stack.
   */
  protected function pushTransaction($transactionId) {
    //
    // Transaction can only be added to list once.
    //
    $keys = array_flip($this->transactions);
    if (!isset($keys[$transactionId])) {
      $this->transactions[] = $transactionId;
    }
    else {
      throw new AblePolecat_Session_Exception(sprintf("Transaction [id:%s] is already on session stack.", $transactionId));
    }
    return count($this->transactions);
  }
  
  /**
   * Removes given transaction from session stack.
   * 
   * @param string $tansactionId ID of given transaction.
   *
   * @return int Number of transactions on stack.
   */
  protected function removeTransaction($transactionId) {
    //
    // Transaction can only be added to list once.
    //
    $key = array_search($transactionId, $this->transactions);
    if ($key) {
      unset($this->transactions[$key]);
    }
    else {
      $message = sprintf("Transaction [id:%s] is not on session stack.", $transactionId);
      // throw new AblePolecat_Session_Exception($message);
      trigger_error($message, E_USER_ERROR);
    }
    return count($this->transactions);
  }
  
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
   * Returns a service initiator by class id.
   *
   * @param string $id UUID of service initiator class.
   *
   * @return AblePolecat_Service_InitiatorInterface or NULL.
   */
  protected function getServiceInitiator($id) {
    
    $ServiceInitiator = NULL;
    
    if (!isset($this->ServiceInitiators)) {
      $this->ServiceInitiators = array();
      
      //
      // Map registered service clients client id => class name
      // These are not loaded unless needed to avoid unnecessary overhead of creating a 
      // client connection.
      //
      $ServiceInitiators = $this->getClassRegistry()->getInterfaceImplementations('AblePolecat_Service_InitiatorInterface');
      isset($ServiceInitiators[AblePolecat_Registry_Class::KEY_ARTICLE_ID]) ? $ServiceInitiatorsById = $ServiceInitiators[AblePolecat_Registry_Class::KEY_ARTICLE_ID] : $ServiceInitiatorsById = array();
      foreach ($ServiceInitiatorsById as $RegistryEntryId => $RegistryEntry) {
        $this->ServiceInitiators[$RegistryEntryId] = $RegistryEntry->name;
      }
    }
    if (isset($this->ServiceInitiators[$id])) {
      $ServiceInitiator = $this->ServiceInitiators[$id];
      if (!is_object($ServiceInitiator)) {
        $this->ServiceInitiators[$id] = $this->getClassRegistry()->loadClass($ServiceInitiator);
        $ServiceInitiator = $this->ServiceInitiators[$id];
      }
    }
    if (!isset($ServiceInitiator) || !is_a($ServiceInitiator, 'AblePolecat_Service_InitiatorInterface')) {
      throw new AblePolecat_Service_Exception("Failed to load service or service client identified by '$id'");
    }
    return $ServiceInitiator;
  }
  
  /**
   * Iniitialize service bus.
   *
   * @return bool TRUE if configuration is valid, otherwise FALSE.
   */
  protected function initialize() {
    $this->ClassRegistry = NULL;
    $this->ServiceInitiators = NULL;
    $this->Messages = NULL;
    $this->transactions = array();
  }
}