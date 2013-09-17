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
interface AblePolecat_Service_BusInterface extends AblePolecat_Service_Interface {
  
  /**
   * Add a service client to the bus.
   * 
   * @param string $class_name The name of class to register.
   *
   * @throw AblePolecat_Service_Exception if client cannot be set.
   */
  public function setClient($class_name);
  
  /**
   * Returns a service client by class name.
   *
   * @param string $class_name Name of the service client class.
   *
   * @return AblePolecat_Service_ClientInterface or NULL.
   */
  public function getClient($class_name);
}

class AblePolecat_Service_Bus implements AblePolecat_Service_BusInterface {
  
  const UUID              = '3d50dbb0-715e-11e2-bcfd-0800200c9a66';
  const NAME              = 'Able Polecat Service Bus';
  
  /**
   * @var object Singleton instance
   */
  private static $ServiceBus;
  
  /**
   * @var Array Web service clients.
   */
  protected $Clients;
  
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
  }
  
  /**
   * Add a service client to the bus.
   * 
   * @param string $class_name The name of class to register.
   *
   * @throw AblePolecat_Service_Exception if client cannot be set.
   */
  public function setClient($class_name) {
    
    //
    // Class name is internal id.
    //
    if (!isset($this->Clients[$class_name])) {
      //
      // Preliminary checks.
      // Class must be registered with Able Polecat.
      // Class must implement AblePolecat_Service_ClientInterface.
      //
      if (AblePolecat_ClassRegistry::isLoadable($class_name) &&
          is_subclass_of($class_name, 'AblePolecat_Service_ClientInterface')) {
          //
          // Do not load the client until first call to getClient().
          //
      }
      else {
        throw new AblePolecat_Service_Exception("Able Polecat rejected attempt to add client type $class_name to service bus.",
          ABLE_POLECAT_EXCEPTION_SVC_CLIENT_TYPE_FAIL
        );
      }
    }
  }
  
  /**
   * Returns a service client by class name.
   *
   * @param string $class_name Name of the service client class.
   *
   * @return AblePolecat_Service_ClientInterface or NULL.
   */
  public function getClient($class_name) {
    $Client = NULL;
    if (isset($this->Clients[$class_name])) {
      $Client = $this->Clients[$class_name];
    }
    else {
      $Client = AblePolecat_ClassRegistry::loadClass($class_name);
      $this->Clients[$class_name] = $Client;
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
      $ServiceBus->Clients = array();
      self::$ServiceBus = $ServiceBus;
      
    }					
    return self::$ServiceBus;
  }
}