<?php
/**
 * @file      polecat/core/Service/Bus.php
 * @brief     Provides a channel for Able Polecat services to communicate with one another.
 *
 * 1. Route messages between services implemented in Able Polecat.
 * 2. Resolve contention between services.
 * 3. Control data transformation and exchange (DTX) between services.
 * 4. Marshal redundant resources (e.g. web service client connections).
 * 5. Handle messaging, exceptions, logging etc.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Response.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Cached.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xhtml.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xml.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Initiator.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction.php')));

/**
 * Manages multiple web services initiator connections and routes messages
 * between these and the application in scope.
 */

class AblePolecat_Service_Bus extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_SubjectInterface {
  
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
   *        2.2.2 If access denied and resourceDenyCode == 403 generate access denied 
   *              response and GOTO 7
   *        2.2.3 If access denied and resourceDenyCode == 401 generate authentication 
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
        $message = sprintf("%s request dispatched to service bus by %s agent.", 
          $Message->getMethod(),
          $Agent->getName()
        );
        AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
    
        //
        // Get resource registration info.
        //
        $ResourceRegistration = $this->getResourceRegistration($Message);
        
        //
        // If method is GET and resource is not restricted, check cache first.
        //
        if (($Message->getMethod() == 'GET') && (200 == $ResourceRegistration->getResourceDenyCode())) {
          //
          // Check current cache entry against modified dates for both resource and response objects.
          //
          $ResponseRegistration = $this->getResponseRegistration(
            $ResourceRegistration->getResourceId(), 
            $ResourceRegistration->getResourceName(), 
            200
          );
          $CacheRegistration = $this->getCacheRegistration($ResourceRegistration->getResourceId(), 200);
          if (($CacheRegistration->getLastModifiedTime() > $ResourceRegistration->getLastModifiedTime()) && 
              ($CacheRegistration->getLastModifiedTime() > $ResponseRegistration->getLastModifiedTime())) {
            //
            // Generate response from cache entry
            //
            $Response = AblePolecat_Message_Response_Cached::create();
            $Response->setCachedResponse($CacheRegistration);
            AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'Using cached response.');
          }
          else {
            AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'Cached response not available or outdated.');
          }
        }
        
        //
        // If no cached response was available, generate a new response.
        //
        if (!isset($Response)) {
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
              AblePolecat_Host::getSessionNumber(),
              $ResourceRegistration->getPropertyValue('resourceId'), 
              AblePolecat_TransactionInterface::TX_STATE_COMMITTED)
            );
          $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
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
            $transactionClassName = $ResourceRegistration->getTransactionClassName();
            if (!isset($transactionClassName)) {
              switch ($Message->getMethod()) {
                default:
                  throw new AblePolecat_Service_Exception(sprintf("No transaction class registered for %s method on resource named %s",
                    $Message->getMethod(),
                    $ResourceRegistration->getResourceName()
                  ));
                  break;
                case 'GET':
                  $transactionClassName = 'AblePolecat_Transaction_Get_Resource';
                  break;
              }
            }
            else {
              if ($this->getClassRegistry()->isLoadable($transactionClassName)) {
                if (!is_subclass_of($transactionClassName, 'AblePolecat_TransactionInterface')) {
                  throw new AblePolecat_Service_Exception(sprintf("Transaction class registered for %s method on resource named %s does not implement AblePolecat_TransactionInterface",
                    $transactionClassName,
                    $Message->getMethod(),
                    $ResourceRegistration->getResourceName()
                  ));
                }
              }
              else {
                throw new AblePolecat_Service_Exception(sprintf("Transaction class %s is not loadable.",
                  $transactionClassName
                ));
              }
            }
            
            // 
            // Start or resume the transaction
            // @todo: $parentTransactionId must be parent (object)
            //
            $Transaction = $this->getClassRegistry()->loadClass(
              $transactionClassName,
              $this->getDefaultCommandInvoker(),
              $Agent,
              $Message,
              $ResourceRegistration,
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
   * Return registration data on resource corresponding to request URI/path.
   *
   * Able Polecat expects the part of the URI, which follows the host or virtual host
   * name to define a 'resource' on the system. This function returns the data (model)
   * corresponding to request. If no corresponding resource is located on the system, 
   * or if an application error is encountered along the way, Able Polecat has a few 
   * built-in resources to deal with these situations.
   *
   * NOTE: Although a 'resource' may comprise more than one path component (e.g. 
   * ./books/[ISBN] or ./products/[SKU] etc), an Able Polecat resource is identified by
   * the first part only (e.g. 'books' or 'products') combined with a UUID. Additional
   * path parts are passed to the top-level resource for further resolution. This is 
   * why resource classes validate the URI, to ensure it follows expectations for syntax
   * and that request for resource can be fulfilled. In short, the Able Polecat server
   * really only fulfils the first part of the resource request and delegates the rest to
   * the 'resource' itself.
   *
   * @see AblePolecat_ResourceAbstract::validateRequestPath()
   *
   * @param AblePolecat_Message_RequestInterface $Request
   * 
   * @return AblePolecat_Registry_Entry_Resource
   */
  protected function getResourceRegistration(AblePolecat_Message_RequestInterface $Request) {
    
    $ResourceRegistration = AblePolecat_Registry_Entry_Resource::create();
    
    //
    // Extract the part of the URI, which defines the resource.
    //
    $requestPathInfo = $Request->getRequestPathInfo();
    isset($requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME]) ? $resourceName = $requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME] : $resourceName  = NULL;    
    if (isset($resourceName)) {
      $ResourceRegistration->resourceName = $resourceName;
      $ResourceRegistration->hostName = $Request->getHostName();
      //
      // Look up (first part of) resource name in database
      //
      $sql = __SQL()->          
          select('resourceClassName', 'resourceId', 'transactionClassName', 'authorityClassName', 'resourceDenyCode', 'lastModifiedTime')->
          from('resource')->
          where(sprintf("`resourceName` = '%s' AND `hostName` = '%s'", $resourceName, $Request->getHostName()));
      $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        if (isset($classInfo[0])) {
          $ResourceRegistration->resourceId = $classInfo[0]['resourceId'];
          $ResourceRegistration->resourceClassName = $classInfo[0]['resourceClassName'];
          $ResourceRegistration->transactionClassName = $classInfo[0]['transactionClassName'];
          $ResourceRegistration->resourceDenyCode = $classInfo[0]['resourceDenyCode'];
          $ResourceRegistration->authorityClassName = $classInfo[0]['authorityClassName'];
          $ResourceRegistration->lastModifiedTime = $classInfo[0]['lastModifiedTime'];
        }
      }
    }
    if (!isset($ResourceRegistration->resourceClassName)) {
      //
      // Request did not resolve to a registered resource class.
      // Log status and return one of the 'built-in' resources.
      //
      $message = sprintf("Request did not resolve to a registered resource (resource=%s; path=%s; host=%s).",
        $resourceName, 
        $Request->getRequestPath(),
        $Request->getHostName()
      );
      AblePolecat_Command_Log::invoke($this->getDefaultCommandInvoker(), $message, AblePolecat_LogInterface::STATUS);
      
      $ResourceRegistration->transactionClassName = NULL;
      $ResourceRegistration->authorityClassName = NULL;
      $ResourceRegistration->resourceDenyCode = 200;
      switch ($resourceName) {
        default:
          $ResourceRegistration->resourceId = AblePolecat_Resource_Error::getId();
          $ResourceRegistration->resourceClassName = 'AblePolecat_Resource_Error';
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_ACK:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME:
          $ResourceRegistration->resourceId = AblePolecat_Resource_Ack::getId();
          $ResourceRegistration->resourceClassName = 'AblePolecat_Resource_Ack';
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_UTIL:
          $ResourceRegistration->resourceId = AblePolecat_Resource_Restricted_Util::getId();
          $ResourceRegistration->resourceClassName = 'AblePolecat_Resource_Restricted_Util';
          switch ($Request->getMethod()) {
            default:
              break;
            case 'GET':
              $ResourceRegistration->authorityClassName = 'AblePolecat_Transaction_AccessControl_Authority';
              $ResourceRegistration->resourceDenyCode = 401;
              break;
            case 'POST':
              $ResourceRegistration->transactionClassName = 'AblePolecat_Transaction_AccessControl_Authority';
              $ResourceRegistration->authorityClassName = NULL;
              $ResourceRegistration->resourceDenyCode = 403;
              break;
          }
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL:
          $ResourceRegistration->resourceId = AblePolecat_Resource_Install::getId();
          $ResourceRegistration->resourceClassName = 'AblePolecat_Resource_Install';
          break;
      }
    }
    
    //
    // Update cache entry if resource class file has been modified since last resource registry entry update.
    //
    if (isset($ResourceRegistration->resourceClassName)) {
      $ClassRegistration = $this->getClassRegistry()->isLoadable($ResourceRegistration->resourceClassName);
      if ($ClassRegistration && isset($ClassRegistration->classLastModifiedTime)) {
        if ($ClassRegistration->classLastModifiedTime > $ResourceRegistration->lastModifiedTime) {
          $sql = __SQL()->          
            update('resource')->
            set('lastModifiedTime')->
            values($ClassRegistration->classLastModifiedTime)->
            where(sprintf("resourceId = '%s'", $ResourceRegistration->resourceId));
          $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
          if ($CommandResult->success()) {
            $ResourceRegistration->lastModifiedTime = $ClassRegistration->classLastModifiedTime;
          }
        }
      }
    }
    
    return $ResourceRegistration;
  }
  
  /**
   * Check [cache] for a timely and relevant response to request for resource.
   *
   * @param string $resourceId
   * @param int $statusCode
   *
   * @return AblePolecat_Registry_Entry_Cache
   */
  protected function getCacheRegistration($resourceId, $statusCode) {
    
    $CacheRegistration = AblePolecat_Registry_Entry_Cache::create();
    $CacheRegistration->resourceId = $resourceId;
    $CacheRegistration->statusCode = $statusCode;
    
    $sql = __SQL()->          
      select('resourceId', 'statusCode', 'mimeType', 'lastModifiedTime', 'cacheData')->
      into('cache')->
      where(sprintf("`resourceId` = '%s' AND `statusCode` = %d", $resourceId, $statusCode));
    $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_Host::getUserAgent(), $sql);
    if ($CommandResult->success() && is_array($CommandResult->value())) {
      $registrationInfo = $CommandResult->value();
      if (isset($registrationInfo[0])) {
        isset($registrationInfo[0]['mimeType']) ? $CacheRegistration->mimeType = $registrationInfo[0]['mimeType'] : NULL;
        isset($registrationInfo[0]['lastModifiedTime']) ? $CacheRegistration->lastModifiedTime = $registrationInfo[0]['lastModifiedTime'] : NULL;
        isset($registrationInfo[0]['cacheData']) ? $CacheRegistration->cacheData = $registrationInfo[0]['cacheData'] : NULL;
      }
    }
    return $CacheRegistration;
  }
  
  /**
   * Prepare an HTTP response corresponding to the given resource and status code.
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
    // Check cache first.
    //
    $CacheRegistration = $this->getCacheRegistration($Resource::getId(), $Transaction->getStatusCode());
    
    //
    // Search core database for corresponding response registration.
    //
    $ResponseRegistration = $this->getResponseRegistration($Resource->getId(), $Resource->getName(), $Transaction->getStatusCode());
    $responseClassName = $ResponseRegistration->getResponseClassName();
    if(isset($responseClassName)) {
      //
      // @todo: load response class and set entity body.
      //
      $Response = $this->getClassRegistry()->loadClass($responseClassName, $ResponseRegistration);
      $Response->setEntityBody($Resource);
    }
    else {
      //
      // No response registration record; use one of the core response classes.
      //
      $headerFields = array();
      switch ($Resource::getName()) {
        default:
          $Response = AblePolecat_Message_Response_Xml::create($ResponseRegistration);
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL:
          $Response = AblePolecat_Message_Response_Xhtml::create($ResponseRegistration);
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_FORM:
          $Response = AblePolecat_Message_Response_Xhtml::create($ResponseRegistration);
          $Response->setEntityBody($Resource);
          break;
      }
      $Response->setEntityBody($Resource);
    }    
    return $this->updateCache(
      $CacheRegistration,
      $ResourceRegistration,
      $ResponseRegistration,
      $Response
    );
  }
  
  /**
   * Return registration data on response corresponding to the given resource and status code.
   *
   * @param string $resourceId
   * @param int $statusCode
   * 
   * @return AblePolecat_Message_ResponseInterface
   */
  protected function getResponseRegistration($resourceId, $resourceName, $statusCode) {
    
    $ResponseRegistration = AblePolecat_Registry_Entry_Response::create();
    $ResponseRegistration->resourceId = $resourceId;
    $ResponseRegistration->resourceName = $resourceName;
    $ResponseRegistration->statusCode = $statusCode;
    
    //
    // Search database table [response] for a corresponding registration record.
    //
    $sql = __SQL()->          
      select('responseClassName', 'docType', 'defaultHeaders', 'templateFullPath', 'lastModifiedTime')->
      from('response')->
      where(sprintf("`resourceId` = '%s' AND `statusCode` = %d", $ResponseRegistration->resourceId, $ResponseRegistration->statusCode));
    $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    if ($CommandResult->success() && is_array($CommandResult->value())) {
      $registrationInfo = $CommandResult->value();
      if (isset($registrationInfo[0])) {
        $ResponseRegistration->responseClassName = $registrationInfo[0]['responseClassName'];
        isset($registrationInfo[0]['docType']) ? $ResponseRegistration->unserializeDocType($registrationInfo[0]['docType']) : NULL;
        isset($registrationInfo[0]['defaultHeaders']) ? $ResponseRegistration->defaultHeaders = unserialize($registrationInfo[0]['defaultHeaders']) : NULL;
        isset($registrationInfo[0]['templateFullPath']) ? $ResponseRegistration->templateFullPath = $registrationInfo[0]['templateFullPath'] : NULL;
        isset($registrationInfo[0]['lastModifiedTime']) ? $ResponseRegistration->lastModifiedTime = $registrationInfo[0]['lastModifiedTime'] : NULL;
      }
    }
    
    //
    // Update cache entry if response class and corresponding template files have been modified since last 
    // response registry entry update.
    //
    $lastModifiedTime = $ResponseRegistration->lastModifiedTime;
    if (isset($ResponseRegistration->responseClassName)) {
      $responseClassRegistration = $this->getClassRegistry()->isLoadable($ResponseRegistration->responseClassName);
      if ($responseClassRegistration && isset($responseClassRegistration->classLastModifiedTime)) {
        if ($responseClassRegistration->classLastModifiedTime > $ResponseRegistration->lastModifiedTime) {
          //
          // Response class file has been modified since last response registry entry.
          //
          $lastModifiedTime = $responseClassRegistration->classLastModifiedTime;
        }
      }
    }
    if (isset($ResponseRegistration->templateFullPath)) {
      $templateStat = stat($ResponseRegistration->templateFullPath);
      if ($templateStat && isset($templateStat['mtime'])) {
        if ($templateStat['mtime'] > $ResponseRegistration->lastModifiedTime) {
          //
          // Template file has been modified since last response registry entry.
          //
          $lastModifiedTime = $templateStat['mtime'];
        }
      }
    }
    
    if ($lastModifiedTime != $ResponseRegistration->lastModifiedTime) {
      $sql = __SQL()->          
        update('response')->
        set('lastModifiedTime')->
        values($lastModifiedTime)->
        where(sprintf("`resourceId` = '%s' AND `statusCode` = %d", $ResponseRegistration->resourceId, $ResponseRegistration->statusCode));
      $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
      if ($CommandResult->success()) {
        $ResponseRegistration->lastModifiedTime = $lastModifiedTime;
      }
    }
    
    return $ResponseRegistration;
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
        $Resource = AblePolecat_Resource_Core::wakeup(
          $this->getDefaultCommandInvoker(),
          'AblePolecat_Resource_Error',
          'Transaction failed',
          $Exception->getMessage()
        );
        break;
      case 'AblePolecat_Resource_Ack':
      case 'AblePolecat_Resource_Install':
        //
        // If requested resource is ACK, ignore.
        //
        $Resource = AblePolecat_Resource_Core::wakeup(
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
      $ServiceInitiators = $this->getClassRegistry()->getClassListByKey(AblePolecat_Registry_Class::KEY_INTERFACE, 'AblePolecat_Service_InitiatorInterface');
      foreach ($ServiceInitiators as $className => $classInfo) {
        $Id = $classInfo[AblePolecat_Registry_Class::KEY_ARTICLE_ID];
        $this->ServiceInitiators[$Id] = $className;
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
   * Update [cache] with response corresponding to requested resource and status code.
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
   * @param AblePolecat_Registry_Entry_Cache $CacheRegistration
   * @param AblePolecat_Registry_Entry_Resource $ResourceRegistration
   * @param AblePolecat_Registry_Entry_Response $ResponseRegistration
   * @param AblePolecat_MessageInterface $Message
   *
   * @return AblePolecat_MessageInterface
   */
  protected function updateCache(
    AblePolecat_Registry_Entry_Cache $CacheRegistration,
    AblePolecat_Registry_Entry_Resource $ResourceRegistration,
    AblePolecat_Registry_Entry_Response $ResponseRegistration,
    AblePolecat_MessageInterface $Message
  ) {
    
    if (is_a($Message, 'AblePolecat_Message_ResponseInterface')) {
        
        $now = time();
        $sql = __SQL()->          
          replace('resourceId', 'statusCode', 'mimeType', 'lastModifiedTime', 'cacheData')->
          into('cache')->
          values($Message->getResourceId(), $Message->getStatusCode(), $Message->getMimeType(), $now, $Message->getEntityBody());
        $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_Host::getUserAgent(), $sql);
    }
    return $Message;
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