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
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource', 'Registration.php')));
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
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent with access to requested service.
   * @param AblePolecat_MessageInterface $Message
   */
  public function dispatch(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_MessageInterface $Message) {
    
    $Response = NULL;
    
    try {
      if (is_a($Message, 'AblePolecat_Message_RequestInterface')) {
        //
        // Get resource registration info.
        //
        $ResourceRegistration = $this->getResourceRegistration($Message);
        
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
          where(sprintf("`sessionId` = '%s' AND `requestMethod` = '%s' AND `resourceId` = '%s' AND `status` != '%s'", 
            AblePolecat_Session::getId(),
            $Message->getMethod(),
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
        else {
          //
          // Start new transaction.
          //
          // $transactionId = $Records[0]['transactionId'];
          // $savepointId = $Records[0]['savepointId'];
        }
        
        //
        // Start or resume the transaction
        //
        $Transaction = $this->getClassRegistry()->loadClass(
          'AblePolecat_Transaction_Get_Resource',
          $this->getDefaultCommandInvoker(),
          $Agent,
          $Message,
          $ResourceRegistration,
          $transactionId,
          $savepointId,
          $parentTransactionId
        );
        $Resource = $Transaction->start();
        $Response = $this->getResponse($Transaction, $Resource);
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
   * Return the data model (resource) corresponding to request URI/path.
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
   * @return AblePolecat_ResourceInterface
   */
  protected function getResourceRegistration(AblePolecat_Message_RequestInterface $Request) {
    
    $ResourceRegistration = AblePolecat_Resource_Registration::create();
    
    //
    // Extract the part of the URI, which defines the resource.
    //
    $requestPathInfo = $Request->getRequestPathInfo();
    isset($requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME]) ? $resourceName = $requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME] : $resourceName  = NULL;    
    if (isset($resourceName)) {
      $ResourceRegistration->resourceName = $resourceName;
      //
      // Look up (first part of) resource name in database
      //
      $sql = __SQL()->          
          select('resourceClassName', 'resourceId', 'resourceAuthorityClassName', 'resourceDenyCode')->
          from('resource')->
          where(sprintf("resourceName = '%s'", $resourceName));
      $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        if (isset($classInfo[0])) {
          $ResourceRegistration->resourceId = $classInfo[0]['resourceId'];
          $ResourceRegistration->resourceClassName = $classInfo[0]['resourceClassName'];
          $ResourceRegistration->resourceAuthorityClassName = $classInfo[0]['resourceAuthorityClassName'];
          $ResourceRegistration->resourceDenyCode = $classInfo[0]['resourceDenyCode'];
        }
      }
    }
    if (!isset($ResourceRegistration->resourceClassName)) {
      //
      // Request did not resolve to a registered resource class.
      // Return one of the 'built-in' resources.
      //
      if ($resourceName === AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME) {
        $ResourceRegistration->resourceId = self::DEFAULT_RESOURCE_ID;
        $ResourceRegistration->resourceClassName = 'AblePolecat_Resource_Ack';
        $ResourceRegistration->resourceAuthorityClassName = NULL;
        $ResourceRegistration->resourceDenyCode = 0;
      }
      else {
        $ResourceRegistration->resourceId = self::NOT_FOUND_RESOURCE_ID;
        $ResourceRegistration->resourceClassName = 'AblePolecat_Resource_Search';
        $ResourceRegistration->resourceAuthorityClassName = NULL;
        $ResourceRegistration->resourceDenyCode = 0;
      }
    }
    return $ResourceRegistration;
  }
  
  /**
   * @param AblePolecat_TransactionInterface $Transaction
   * @param AblePolecat_ResourceInterface $Resource
   *
   * @return AblePolecat_Message_ResponseInterface
   */
  protected function getResponse(AblePolecat_TransactionInterface $Transaction, AblePolecat_ResourceInterface $Resource) {
    
    //
    // @todo: deal with different status codes.
    //
    $Response = AblePolecat_Message_Response::create($Transaction->getStatusCode());
    $Response->setEntityBody($Resource);
    return $Response;
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