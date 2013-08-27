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

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Service.php');

class AblePolecat_Service_Bus implements AblePolecat_Service_BusInterface {
  
  const UUID              = '3d50dbb0-715e-11e2-bcfd-0800200c9a66';
  const NAME              = 'Able Polecat Service Bus';
  
  /**
   * @var object Singleton instance
   */
  private static $m_Instance;
  
  /**
   * @var resource Database connection.
   */
  private $m_Database = NULL;
  
  /**
   * @var resource A prepared PDO statement for setting locks on service requests.
   */
  private $m_InsertLockStatement = null;
  
  /**
   * @var Array Web service clients.
   */
  protected $m_Clients;
  
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
    
    //
    // Get connection to the database.
    //
    if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_DB_MYSQL)) {
      $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
      if (isset($Environment)) {
        $this->m_Database = $Environment->getDb();
      }
      if (!isset($this->m_Database)) {
        throw new AblePolecat_Log_Exception("Not connected to Able Polecat database", 
          AblePolecat_Log_Exception::ERROR_LOG_INVALID_DB);
      }
      
      //
      // Prepare parameterized PDO statement for inserting log messages.
      //
      $sql = "INSERT locks(service, id, createdbyid) VALUES(:service, :id, :createdbyid)";
      $this->m_InsertLockStatement = $this->m_Database->prepareStatement($sql);
    }
    
  }
  
  /**
   * Close connection and destroy current session variables relating to connection.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   */
  public function close(AblePolecat_AccessControl_AgentInterface $Agent = NULL) {
  }
  
  /**
   * Send asynchronous message over client connection.
   *
   * @param AblePolecat_MessageInterface $Message.
   */
  public function dispatch(AblePolecat_MessageInterface $Message) {
  }
  
  /**
   * Returns a connection to the Google client.
   */
  public function getGoogleClient() {
    if (!isset($this->m_Clients[AblePolecat_Service_Client_Google::getId()])) {
      $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
      $this->m_Clients[AblePolecat_Service_Client_Google::getId()] = $Environment->loadClass('AblePolecat_Service_Client_Google');
    }
    return $this->m_Clients[AblePolecat_Service_Client_Google::getId()];
  }
  /**
   * Returns a connection to the ProvideX client.
   */
  public function getProvideXClient() {
    if (!isset($this->m_Clients[AblePolecat_Service_Client_ProvideX::getId()])) {
      $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
      $this->m_Clients[AblePolecat_Service_Client_ProvideX::getId()] = $Environment->loadClass('AblePolecat_Service_Client_ProvideX');
    }
    return $this->m_Clients[AblePolecat_Service_Client_ProvideX::getId()];
  }
  
  /**
   * Returns a connection to the Salesforce.com client.
   */
  public function getSalesforceClient() {
    if (!isset($this->m_Clients[AblePolecat_Service_Client_Salesforce::getId()])) {
      $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
      $this->m_Clients[AblePolecat_Service_Client_Salesforce::getId()] = $Environment->loadClass('AblePolecat_Service_Client_Salesforce');
    }
    return $this->m_Clients[AblePolecat_Service_Client_Salesforce::getId()];
  }
  
  /**
   * Sets a lock for the given service.
   *
   * @param string $service The service which is subject to the lock.
   * @param string $id The ID of the object of the service.
   * @param int $createdbyid The ID of the user setting the lock. 
   *
   * @return TRUE if the lock was set, otherwise FALSE.
   */
  public function setLock($service, $id, $createdbyid = 0) {
    
    $result = FALSE;
    if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_DB_MYSQL)) {
      if (isset($this->m_InsertLockStatement)) {
        $this->m_InsertLockStatement->bindParam(':service', $service);
        $this->m_InsertLockStatement->bindParam(':id', $id);
        $this->m_InsertLockStatement->bindParam(':createdbyid', $createdbyid);
        $result = $this->m_InsertLockStatement->execute();
      }
    }
    return $result;
  }
  
  /**
   * Serialize configuration and connection settings prior to going out of scope.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   */
  public function sleep(AblePolecat_AccessControl_AgentInterface $Agent = NULL) {
  }
  
  /**
   * Open a new connection or resume a prior connection.
   *
   * @param AblePolecat_AccessControl_AgentInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Api_ClientAbstract Initialized/connected instance of class ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_AgentInterface $Agent = NULL) {
    
    $Client = new AblePolecat_Service_Client_Google();
    return $Client;
  }
  
  /**
   * Initialize singleton instance of this class.
   */
  public static function getInstance() {
    if (!isset(self::$m_Instance)) {
      self::$m_Instance = new self();
    }					
    return self::$m_Instance;
  }
  
  final protected function __construct() {
    $this->m_Clients = array();
    $this->initialize();
  }
}