<?php
/**
 * @file: Bus.php
 * Provides a channel for Able Polecat services to communicate with one another.
 *
 * Route messages between services implemented in Able Polecat.
 * Resolve contention between services.
 * Control data transformation and exchange (DTX) between services.
 * Marshal redundant resources (e.g. web service client connections).
 * Handle messaging, exceptions, logging etc.
 * 
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Service', 'Client.php')));

/**
 * Manages multiple web services client connections and routes messages
 * between these and the application in scope.
 */

 class AblePolecat_Service_Bus  {
  
  const UUID              = '3d50dbb0-715e-11e2-bcfd-0800200c9a66';
  const NAME              = 'Able Polecat Service Bus';
  
  const REQUEST           = 'request';
  const RESPONSE          = 'response';
  
  /**
   * @var object Singleton instance
   */
  private static $ServiceBus;
  
  /**
   * @var Array Web service clients.
   */
  protected $Clients;
  
  /**
   * @todo: message queue
   */
  protected $Messages;
  
  /**
   * @todo: transaction log
   */
  protected $Transactions;
  
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
  
  /**
   * Iniitialize client configuration settings prior to attempting a connection.
   *
   * @return bool TRUE if configuration is valid, otherwise FALSE.
   */
  protected function initialize() {
    $this->Clients = array();
    
    //
    // Register all message types
    //
    $ClassRegistry = AblePolecat_Server::getClassRegistry();
    if (isset($ClassRegistry)) {
       $ClassRegistry->registerLoadableClass(
        'AblePolecat_Message_Request_Delete',
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Request', 'Delete.php')),
        'create'
      );
       $ClassRegistry->registerLoadableClass(
        'AblePolecat_Message_Request_Get',
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Request', 'Get.php')),
        'create'
      );
      $ClassRegistry->registerLoadableClass(
        'AblePolecat_Message_Request_Post',
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Request', 'Post.php')),
        'create'
      );
      $ClassRegistry->registerLoadableClass(
        'AblePolecat_Message_Request_Put',
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Request', 'Put.php')),
        'create'
      );
      $ClassRegistry->registerLoadableClass(
        'AblePolecat_Message_Response',
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Response.php')),
        'create'
      );
    }
  }
  
  /**
   * Add a service client to the bus.
   * 
   * @param string $id UUID of service client class.
   * @param string $class_name The name of class to register.
   *
   * @throw AblePolecat_Service_Exception if client cannot be set.
   */
  protected function registerClient($id, $class_name) {
    
    $ClassRegistry = AblePolecat_Server::getClassRegistry();
    if (isset($ClassRegistry)) {
      if (!isset($this->Clients[$id])) {
        //
        // Preliminary checks.
        // Class must be registered with Able Polecat.
        // Class must implement AblePolecat_Service_ClientInterface.
        //
        if ($ClassRegistry->isLoadable($class_name) &&
            is_subclass_of($class_name, 'AblePolecat_Service_ClientInterface')) {
            //
            // Do not load the client until first call to getClient().
            //
            $this->Clients[$id] = $class_name;
        }
        else {
          throw new AblePolecat_Service_Exception("Able Polecat rejected attempt to add client type $class_name to service bus.",
            ABLE_POLECAT_EXCEPTION_SVC_CLIENT_TYPE_FAIL
          );
        }
      }
    }
  }
  
  /**
   * Add a message to the queue.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent with access to requested service.
   * @param AblePolecat_MessageInterface $Message
   */
  public function dispatch(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_MessageInterface $Message) {
    
    //
    // Prepare response
    //
    $Response = AblePolecat_Server::getClassRegistry()->
      loadClass('AblePolecat_Message_Response');
    
    //
    // Is it request or response?
    //
    $subclass = FALSE;
    if (is_a($Message, 'AblePolecat_Message_RequestInterface')) {
      $subclass = self::REQUEST;
    }
    else if (is_a($Message, 'AblePolecat_Message_ResponseInterface')) {
      $subclass = self::RESPONSE;
    }
    
    //
    // @todo: serialize message to log.
    // Log is used to reload unhandled messages in event of unexpected shutdown.
    //
    
    //
    // Determine target client
    //
    $clientId = NULL;
    switch ($subclass) {
      default:
        break;
      case self::REQUEST:
        $clientId = $Message->getResource();
        break;
      case self::RESPONSE:
        break;
    }
    $Client = $this->getClient($clientId);
    
    //
    // @todo: dispatch the message.
    //
    if (isset($Client)) {
      try { 
        $Response = $Client->prepare($Agent, $Message)->dispatch(); 
      } 
      catch(AblePolecat_Service_Client_Exception $Exception) {
        //
        // @todo: this will only happen if client cannot dispatch message
        // 1. see if problem is with message, if yes return error response
        // 2. otherwise client is busy, queue message for later dispatch
        //
      }
      if (isset($Response)) {
        //
        // @todo: handle response
        //
      }
    }
    
    //
    // Send response.
    //
    return $Response;
  }
  
  /**
   * Is singleton initialized?
   *
   * @return AblePolecat_Service_Bus or FALSE.
   */
  public static function ready() {
    
    $ready = isset(self::$ServiceBus);
    if ($ready) {
      $ready = self::$ServiceBus;
    }
    return $ready;
  }
  
  /**
   * Registers service clients by id => class name.
   */
  public function registerClients() {
    
    $registeredClasses = AblePolecat_Server::getClassRegistry()->getModuleClasses('interface', 'AblePolecat_Service_ClientInterface');
    foreach($registeredClasses as $moduleName => $clientClasses) {
      foreach($clientClasses as $classId => $className) {
        $this->registerClient($classId, $className);
      }
    }
  }
  
  /**
   * Returns a service client by class id.
   *
   * @param string $id UUID of service client class.
   *
   * @return AblePolecat_Service_ClientInterface or NULL.
   */
  public function getClient($id) {
    
    $Client = NULL;
    if (isset($this->Clients[$id])) {
      $class = $this->Clients[$id];
      if (!is_object($class)) {
        $this->Clients[$id] = AblePolecat_Server::getClassRegistry()->loadClass($class);
      }
      $Client = $this->Clients[$id];
    }
    return $Client;
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // @todo: persist
    //
    self::$ServiceBus = NULL;
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
      $ServiceBus = new AblePolecat_Service_Bus();
      self::$ServiceBus = $ServiceBus;
      
    }					
    return self::$ServiceBus;
  }
  
  final protected function __construct() {
    $this->initialize();
  }
}