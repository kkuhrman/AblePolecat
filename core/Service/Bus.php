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

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Initiator.php')));

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
   * @todo: transaction log
   */
  protected $Transactions;
  
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
      $Resource = $this->getRequestedResource($Agent, $Message);
      $Response = $this->getResponse($Resource);
    } 
    catch(AblePolecat_AccessControl_Exception $Exception) {
      //
      // @todo: save transaction, prepare to listen for next request...
      //
      // throw new AblePolecat_Service_Exception($Exception->getMessage());
      // AblePolecat_Dom::kill($Agent);
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
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent with access to requested service.
   * @param AblePolecat_Message_RequestInterface $Request
   * 
   * @return AblePolecat_ResourceInterface
   */
  protected function getRequestedResource(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_Message_RequestInterface $Request) {
    
    $Resource = NULL;
    
    //
    // Extract the part of the URI, which defines the resource.
    //
    $request_path_info = $Request->getRequestPathInfo();
    isset($request_path_info[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME]) ? $resource_name = $request_path_info[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME] : $resource_name  = NULL;    
    if (isset($resource_name)) {
      //
      // Look up (first part of) resource name in database
      //
      $sql = __SQL()->          
        select('resourceClassName', 'resourceAuthorityClassName')->
        from('resource')->
        where("resourceName = '$resource_name'");      
      $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
      $resourceClassName = NULL;
      $resourceAuthorityClassName = NULL;
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        isset($classInfo[0]['resourceClassName']) ? $resourceClassName = $classInfo[0]['resourceClassName'] : NULL;
        isset($classInfo[0]['resourceAuthorityClassName']) ? $resourceAuthorityClassName = $classInfo[0]['resourceAuthorityClassName'] : NULL;
      }
      if (isset($resourceClassName)) {
        //
        // Resource request resolves to registered class name, try to load.
        // Attempt to load resource class
        //
        $Resource = $this->getClassRegistry()->loadClass($resourceClassName, $Agent);
      }
      else {
        //
        // Request did not resolve to a registered resource class.
        // Return one of the 'built-in' resources.
        //
        if ($resource_name === AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME) {
          require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Ack.php')));
          $Resource = AblePolecat_Resource_Ack::wakeup();
        }
        else {
          require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Search.php')));
          $Resource = AblePolecat_Resource_Search::wakeup();
        }
      }
    }
    else {
      //
      // @todo: why would we ever get here but wouldn't it be bad to not return a resource?
      //
    }
    return $Resource;
  }
  
  /**
   * @param AblePolecat_ResourceInterface $Resource
   *
   * @return AblePolecat_Message_ResponseInterface
   */
  protected function getResponse(AblePolecat_ResourceInterface $Resource) {
    
    $Response = NULL;
    $ResourceClassName = get_class($Resource);
    switch($ResourceClassName) {
      default:
        break;
      case 'AblePolecat_Resource_Ack':
        $version = AblePolecat_Server::getVersion();
        $body = sprintf("<AblePolecat>%s</AblePolecat>", $version);
        $Response = AblePolecat_Message_Response::create(200);
        $Response->body = $body;
        break;
    }
    return $Response;
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
    $this->ServiceInitiators = NULL;
    $this->Messages = NULL;
    $this->Transactions = NULL;
  }
}