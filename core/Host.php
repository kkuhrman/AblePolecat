<?php
/**
 * @file      polecat/core/Host.php
 * @brief     Manages most direct interaction between Able Polecat and PHP global variables.
 * 
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Get.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Post.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Put.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Delete.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Host.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'User.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource', 'Core', 'Factory.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Bus.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Version.php')));

class AblePolecat_Host extends AblePolecat_Command_TargetAbstract {
  
  //
  // Access control id
  //
  const UUID                    = 'd63da8f0-39b0-11e4-916c-0800200c9a66';
  const NAME                    = 'AblePolecat_Host';
  
  //
  // Permitted session variables (not stored in database).
  //
  const POLECAT_INSTALL_TRX     = 'transaction';
  const POLECAT_INSTALL_SAVEPT  = 'save_point';
  const POLECAT_INSTALL_DBNAME  = 'database';
  const POLECAT_INSTALL_DBUSER  = 'user';
  const POLECAT_INSTALL_DBPASS  = 'pass';
  
  /**
   * @var Instance of concrete singleton class.
   */
  private static $Host = NULL;
  
  /**
   * @var Instance of AblePolecat_Message_RequestInterface.
   */
  private $Request;
  
  /**
   * @var AblePolecat_Message_ResponseInterface.
   */
  private $Response;
  
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
    try {
      parent::sleep();
      //
      // Flush output buffer.
      //
      ob_end_flush();
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Output buffering flushed.');
    }
    catch (AblePolecat_Exception $Exception) {
    }
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Host)) {
      //
      // Create instance of Singleton.
      //
      self::$Host = new AblePolecat_Host();
      
      //
      // Initiate session/user.
      //
      $UserMode = AblePolecat_Mode_User::wakeup();
      
      //
      // Preprocess HTTP request.
      //
      $Request = self::getRequest();

      //
      // Save raw HTTP request.
      //
      self::$Host->saveRawRequest();
      
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Able Polecat HOST initialized.');
    }
    return self::$Host;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Command_TargetInterface.
   ********************************************************************************/
  
  /**
   * Execute a command or pass forward on chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public function execute(AblePolecat_CommandInterface $Command) {
    
    $Result = NULL;
    
    switch ($Command::getId()) {
      default:
        break;
      case AblePolecat_Command_Shutdown::UUID:
        //
        // Host shut down command overrides server mode shut down command
        // because it needs to send HTTP response before terminating.
        //
        self::shutdown(
          $Command->getReason(),
          $Command->getMessage(),
          $Command->getStatus()
        );
        break;
    }
    //
    // Pass command to next link in chain of responsibility
    //
    $Result = $this->delegateCommand($Command, $Result);
    return $Result;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_ModeInterface.
   ********************************************************************************/
  
  /**
   * Shut down Able Polecat server and send HTTP response.
   *
   * @param string  $reason   Reason for shut down.
   * @param string  $message  Message associated with shut down request.
   * @param int     $status   Return code.
   */
  public static function shutdown($reason, $message, $status = 0) {
    if (isset(self::$Host)) {
      $shutdownMessage = sprintf("SHUTDOWN - %s. %s. status %d ", $reason, $message, $status);
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $shutdownMessage);
      if (!isset(self::$Host->Response)) {
        $Resource = AblePolecat_Resource_Core_Factory::wakeup(
          self::$Host->getDefaultCommandInvoker(),
          'AblePolecat_Resource_Core_Error',
          $message,
          $reason,
          $status
        );
        self::$Host->Response = AblePolecat_Message_Response_Xml::create(500);
        self::$Host->Response->setEntityBody($Resource);
      }
      self::$Host->Response->send();
    }
    exit($status);
  }
  
  /********************************************************************************
   * Methods not required by implemented interface(s) but public.
   ********************************************************************************/
  
  /**
   * Main point of entry for all Able Polecat page and service requests.
   *
   * Alias for AblePolecat_Host::wakeup()->routeRequest().
   */
  public static function routeRequest() {
    
    if (!isset(self::$Host)) {
      //
      // Create instance of Singleton.
      //
      $Host = AblePolecat_Host::wakeup();
      
      //
      // Boot procedure complete. Close boot log.
      //
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Boot procedure completed successfully.');
      
      //
      // Dispatch request message.
      //
      $Request = self::getRequest();
      if (is_a($Request, 'AblePolecat_Message_Request_Unsupported')) {
        //
        // @todo: 405 Method not allowed.
        // The method specified in the Request-Line is not allowed for the resource identified by the Request-URI. 
        // The response MUST include an Allow header containing a list of valid methods for the requested resource. 
        //
      }
      else {
        // 
        // Get user agent and dispatch request to service bus.
        //
        $Host->Response = AblePolecat_Service_Bus::wakeup(AblePolecat_AccessControl_Agent_User_System::wakeup())->
          dispatch(AblePolecat_AccessControl_Agent_User::wakeup(), $Request);
      }    
      
      //
      // shut down and send response
      //
      AblePolecat_Command_Shutdown::invoke($Host->getDefaultCommandInvoker(), '', '', 0);
    }
    else {
      //
      // Only one call per HTTP request.
      //
      throw new AblePolecat_Host_Exception(
        'Able Polecat is busy routing current request.',
        AblePolecat_Error::ACCESS_INVALID_OBJECT
      );
    }
  }
  
  /**
   * @return AblePolecat_Message_RequestInterface
   */
  public static function getRequest() {
    
    if (isset(self::$Host)) {
      if (!isset(self::$Host->Request)) {
        //
        // Build request.
        //
        isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
        switch ($method) {
          default:
            self::$Host->Request = AblePolecat_Message_Request_Unsupported::create();
            break;
          case 'GET':
            self::$Host->Request = AblePolecat_Message_Request_Get::create();
            break;
          case 'POST':
            self::$Host->Request = AblePolecat_Message_Request_Post::create();
            break;
          case 'PUT':
            self::$Host->Request = AblePolecat_Message_Request_Put::create();
            break;
          case 'DELETE':
            self::$Host->Request = AblePolecat_Message_Request_Delete::create();
            break;
        }
        
        //
        // @todo: handle redirect to search page?
        //
        // $redirect_uri = self::$Host->Request->getRedirectUrl();
        // if ($redirect_uri) {
          // header("Location: $redirect_uri");
          // exit(0);
        // }
        
        $message = sprintf("Preprocessed %s request to '%s' for '%s'.",
          self::$Host->Request->getMethod(),
          self::$Host->Request->getHostName(),
          self::$Host->Request->getRequestPath()
        );
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
      }
    }
    else {
      $message = 'Able Polecat cannot pre-process HTTP request prior to initialization of host object.';
      trigger_error($message, E_USER_ERROR);
    }
    return self::$Host->Request;
  }
  
  /**
   * Helper function - saves some data about raw HTTP request.
   */
  protected function saveRawRequest() {
    if (isset(self::$Host) && isset(self::$Host->Request)) {
      //
      // Check connection to core database.
      //
      $requestId = NULL;
      if (AblePolecat_Mode_Config::coreDatabaseIsReady()) {
        //
        // Log raw request.
        //
        isset($_SERVER['REQUEST_TIME']) ? $requestTime = $_SERVER['REQUEST_TIME'] : $requestTime = time();
        isset($_SERVER['REMOTE_ADDR']) ? $remoteAddress = $_SERVER['REMOTE_ADDR'] : $remoteAddress = 'UNKNOWN';
        isset($_SERVER['REMOTE_PORT']) ? $remotePort = $_SERVER['REMOTE_PORT'] : $remotePort = 'UNKNOWN';
        isset($_SERVER['HTTP_USER_AGENT']) ? $userAgent = $_SERVER['HTTP_USER_AGENT'] : $userAgent = 'UNKNOWN';
        isset($_SERVER['REQUEST_METHOD']) ? $requestMethod = $_SERVER['REQUEST_METHOD'] : $requestMethod = 'UNKNOWN';
        isset($_SERVER['REQUEST_URI']) ? $requestUri = $_SERVER['REQUEST_URI'] : $requestUri = 'UNKNOWN';
        $sql = __SQL()->          
          insert(
            'requestTime', 
            'remoteAddress', 
            'remotePort', 
            'userAgent', 
            'requestMethod', 
            'requestUri')->
          into('request')->
          values(
            $requestTime, 
            $remoteAddress, 
            $remotePort, 
            $userAgent, 
            $requestMethod, 
            $requestUri
          );
        $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), $sql);
        if ($CommandResult->success() && count($CommandResult->value())) {
          $Records = $CommandResult->value();
          isset($Records['lastInsertId']) ? $requestId = $Records['lastInsertId'] : NULL;
        }
      }
      
      self::$Host->Request->setRawRequestLogRecordId($requestId);
    }
    else {
      $message = 'Able Polecat cannot save raw HTTP request prior to initialization of session object.';
      trigger_error($message, E_USER_ERROR);
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
    
  /**
   * Validates given command target as a forward or reverse COR link.
   *
   * @param AblePolecat_Command_TargetInterface $Target.
   * @param string $direction 'forward' | 'reverse'
   *
   * @return bool TRUE if proposed COR link is acceptable, otherwise FALSE.
   */
  protected function validateCommandLink(AblePolecat_Command_TargetInterface $Target, $direction) {
    
    $ValidLink = FALSE;
    
    switch ($direction) {
      default:
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_FWD:
        $ValidLink = is_a($Target, 'AblePolecat_Mode_User');
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Application');
        break;
    }
    return $ValidLink;
  }
    
  /**
   * Extends constructor.
   */
  protected function initialize() {
    //
    // Turn on output buffering.
    //
    ob_start();
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Output buffering started.');
    
    //
    // Access control agent (super user).
    //
    $this->setDefaultCommandInvoker(AblePolecat_AccessControl_Agent_User_System::wakeup());
    
    //
    // Initiate application mode and establish as reverse command target.
    // Application mode wakes up server mode before it can do anything.
    //
    $CommandChain = AblePolecat_Command_Chain::wakeup();
    $ApplicationMode = AblePolecat_Mode_Application::wakeup();
    $CommandChain->setCommandLink($ApplicationMode, $this);
  }
}