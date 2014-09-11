<?php
/**
 * @file      polecat/core/Host.php
 * @brief     Manages most direct interaction between Able Polecat and PHP global variables.
 *
 * Host has the following duties:
 * 1. Marshall web server REQUEST
 * 2. Initiate chain of responsibility (COR - server, application, user, etc)
 * 3. Dispatch marshalled request object
 * 4. Unmarshall RESPONSE, send HTTP response head/body
 * 5. Handle shut down and redirection in the event of error
 * 6. Act as terminal/final command target
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

/**
 * Most current version is loaded from conf file. These are defaults.
 */
define('ABLE_POLECAT_VERSION_NAME', 'DEV-0.6.1');
define('ABLE_POLECAT_VERSION_ID', 'ABLE_POLECAT_CORE_0_6_1_DEV');
define('ABLE_POLECAT_VERSION_MAJOR', '0');
define('ABLE_POLECAT_VERSION_MINOR', '6');
define('ABLE_POLECAT_VERSION_REVISION', '1');

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Target.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Get.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Post.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Put.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Delete.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Host.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Session.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource', 'Error.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Bus.php')));

final class AblePolecat_Host extends AblePolecat_Command_TargetAbstract {
  
  //
  // Access control id
  //
  const UUID                    = 'd63da8f0-39b0-11e4-916c-0800200c9a66';
  const NAME                    = 'Able Polecat Host';
  
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
  
  /**
   * @var string PHP session ID.
   */
  private $sessionId;
  
  /**
   * @var Array Initial PHP session state.
   */
  private $sessionGlobal;
  
  /**
   * @var Next forward target in command chain of responsibility.
   */
  // private $Subordinate;
  
  /**
   * @var Instance of AblePolecat_SessionInterface.
   */
  private $Session;
  
  /**
   * @var string Version number from server config settings file.
   */
  private $version;
  
  /**
   * @var int Error display directive.
   */
  private static $display_errors;
  
  /**
   * @var int Error reporting directive.
   */
  private static $report_errors;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_SubjectInterface.
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
        //
        // End of CoR. FAIL.
        //
        $Result = new AblePolecat_Command_Result();
        break;
      case '7ca0f570-1f22-11e4-8c21-0800200c9a66':
        //
        // Command to shut down indicates abnormal termination
        //
        $Resource = AblePolecat_Resource_Error::wakeup();
        $Resource->Reason = $Command->getReason();
        $Resource->Message = $Command->getMessage();
        self::$Host->Response = AblePolecat_Message_Response::create(500);
        self::$Host->Response->setEntityBody($Resource);
        self::shutdown($Command->getStatus());
        break;
    }
    
    return $Result;
  }
  
  /********************************************************************************
   * Methods not required by implemented interface(s) but public.
   ********************************************************************************/
  
  /**
   * Main point of entry for all Able Polecat page and service requests.
   *
   */
  public static function routeRequest() {
    
    if (!isset(self::$Host)) {
      //
      // Create instance of Singleton.
      //
      self::$Host = new AblePolecat_Host();
      
      //
      // wakeup session mode and get user agent.
      //
      self::$Host->Session = AblePolecat_Mode_Session::wakeup(self::$Host);
            
      //
      // Preprocess HTTP request.
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
        $Agent = self::$Host->Session->getUserAgent();
        self::$Host->Response = AblePolecat_Service_Bus::wakeup(self::$Host->Session)->dispatch($Agent, $Request);
      }
    }
    else {
      //
      // Only one call per HTTP request.
      //
      throw new AblePolecat_Host_Exception(
        'Able Polecat server is already routing the current request.',
        AblePolecat_Error::ACCESS_INVALID_OBJECT
      );
    }
    
    //
    // shut down and send response
    //
    AblePolecat_Host::shutdown();
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
        // Check connection to core database.
        //
        $requestId = NULL;
        if (self::$Host->Session->getDatabaseState(self::$Host, 'connected')) {
          //
          // Log raw request.
          //
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
              $_SERVER['REQUEST_TIME'], 
              $_SERVER['REMOTE_ADDR'],
              $_SERVER['REMOTE_PORT'],
              $_SERVER['HTTP_USER_AGENT'],
              $_SERVER['REQUEST_METHOD'],
              $_SERVER['REQUEST_URI']
            );
          $CommandResult = AblePolecat_Command_DbQuery::invoke(self::$Host->Session, $sql);
          if ($CommandResult->success() && count($CommandResult->value())) {
            $Records = $CommandResult->value();
            isset($Records['lastInsertId']) ? $requestId = $Records['lastInsertId'] : NULL;
          }
        }
        self::$Host->Request->setRawRequestLogRecordId($requestId);
      }
    }
    else {
      $message = 'Able Polecat cannot pre-process HTTP request prior to initialization of host object.';
      trigger_error($message, E_USER_ERROR);
    }
    return self::$Host->Request;
  }
  
  /**
   * @return AblePolecat_Message_RequestInterface
   */
  public static function getSessionId() {
    
    $sessionId = NULL;
    
    if (isset(self::$Host)) {
      $sessionId = self::$Host->sessionId;
    }
    return $sessionId;
  }
  
  /**
   * Get version number of server/core.
   */
  public static function getVersion($as_str = TRUE, $doc_type = 'XML') {
    
    $version = NULL;
    
    //
    // @todo: override defaults with data from core conf file?
    //
    if (isset(self::$Host->version)) {
      if ($as_str) {
        switch ($doc_type) {
          default:
            $version = sprintf("Version %s.%s.%s (%s)",
              self::$Host->version['major'],
              self::$Host->version['minor'],
              self::$Host->version['revision'],
              self::$Host->version['name']
            );
            break;
          case 'XML':
            $version = sprintf("<polecat_version name=\"%s\"><major>%s</major><minor>%s</minor><revision>%s</revision></polecat_version>",
              self::$Host->version['name'],
              strval(self::$Host->version['major']),
              strval(self::$Host->version['minor']),
              strval(self::$Host->version['revision'])
            );
            break;
          //
          // @todo: case 'JSON':
          //
        }
      }
      else {
        $version = self::$Host->version;
      }
    }
    else {
      if ($as_str) {
        $version = sprintf("Version %s.%s.%s (%s)",
          ABLE_POLECAT_VERSION_MAJOR,
          ABLE_POLECAT_VERSION_MINOR,
          ABLE_POLECAT_VERSION_REVISION,
          ABLE_POLECAT_VERSION_NAME
        );
      }
      else {
        $version = array(
          'name' => ABLE_POLECAT_VERSION_NAME,
          'major' => ABLE_POLECAT_VERSION_MAJOR,
          'minor' => ABLE_POLECAT_VERSION_MINOR,
          'revision' => ABLE_POLECAT_VERSION_REVISION,
        );
      }
    }
    return $version;
  }
  
  /**
   * Handle errors triggered by child objects.
   */
  public static function handleError($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL) {
    
    $shutdown = (($errno == E_ERROR) || ($errno == E_USER_ERROR));
    
    //
    // Get error information
    //
    $msg = sprintf("Error in Able Polecat. %d %s", $errno, $errstr);
    
    //
    // Send error information to syslog
    //
    $type = AblePolecat_LogInterface::STATUS;
    switch($errno) {
      default:
        break;
      case E_USER_ERROR:
      case E_ERROR:
        $type = AblePolecat_LogInterface::ERROR;
        break;
      case E_USER_WARNING:
        $type = AblePolecat_LogInterface::WARNING;
        break;
    }
    
    $errorFile = str_replace("\\", "\\\\", $errfile);
    $errorLine = $errline;    
    $errorMessage = $msg;
    $sql = __SQL()->          
      insert(
        'errorType',
        'errorFile', 
        'errorLine', 
        'errorClass', 
        'errorFunction',
        'errorMessage')->
      into('error')->
      values(
        $type,
        $errorFile,
        $errorLine,
        __CLASS__,
        __FUNCTION__,
        $errorMessage
    );
    $CommandResult = AblePolecat_Command_DbQuery::invoke(self::$Host->Session, $sql);
    if (!$CommandResult->success()) {
      //
      // Apparently, no other log facility was available to handle the message
      //
      AblePolecat_Log_Syslog::wakeup()->putMessage($type, $msg);
    }
    if (!isset(self::$Host->Request) || !isset(self::$Host->Request->setRawRequestLogRecordId)) {
      //
      // Error triggered before raw request logged.
      //
      AblePolecat_Log_Boot::wakeup()->putMessage($type, $errorMessage);
    }
    
    if ($shutdown && (self::$display_errors != 0)) {
      $reason = 'Critical Error';
      $code = $errno;
      AblePolecat_Command_Shutdown::invoke(self::$Host, $reason, $msg, $code);
    }
    else if (self::$display_errors != 0) {
      //
      // User induced script to vomit error on screen.
      //
      print('<p>display_errors set to ' . self::$display_errors . '</p>');
      print('<p>' . 
        $msg . 
        '<ul><li>' .
        $errfile . 
        '</li><li>' .
        $errline .
        '</li></ul></p>'
      );
      exit($errno);
    }
    return $shutdown;
  }
  
  /**
   * Handle exceptions thrown by child objects.
   * 
   * @todo: hand control back to the server or otherwise fail gracefully. no WSOD
   */
  public static function handleException(Exception $Exception) {
    //
    // Log exception to database.
    //
    $errorFile = str_replace("\\", "\\\\", $Exception->getFile());
    $errorLine = $Exception->getLine();    
    $errorMessage = $Exception->getMessage();
    $sql = __SQL()->          
      insert(
        'errorType',
        'errorFile', 
        'errorLine', 
        'errorClass', 
        'errorFunction',
        'errorMessage')->
      into('error')->
      values(
        'exception',
        $errorFile,
        $errorLine,
        __CLASS__,
        __FUNCTION__,
        $errorMessage
    );
    $CommandResult = AblePolecat_Command_DbQuery::invoke(self::$Host->Session, $sql);
    if (!$CommandResult->success()) {
      //
      // Apparently, no other log facility was available to handle the message
      //
      AblePolecat_Log_Syslog::wakeup()->putMessage(AblePolecat_LogInterface::WARNING, $errorMessage);
    }
    if (!isset(self::$Host->Request) || !isset(self::$Host->Request->setRawRequestLogRecordId)) {
      //
      // Exception thrown before raw request logged.
      //
      AblePolecat_Log_Boot::wakeup()->putMessage(AblePolecat_LogInterface::WARNING, $errorMessage);
    }
    
    //
    // Send shut down command to server
    //
    $reason = 'Unhandled exception';
    $code = $Exception->getCode();
    AblePolecat_Command_Shutdown::invoke(self::$Host, $reason, $Exception->getMessage(), $code);
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Sets version information from core configuration file.
   */
  private function setVersion($version = NULL) {
    
    if (isset($version['name']) &&
        isset($version['major']) &&
        isset($version['minor']) &&
        isset($version['revision'])) {
        $this->version = array();
      $this->version['name'] = $version['name'];
      $this->version['major'] = $version['major'];
      $this->version['minor'] = $version['minor'];
      $this->version['revision'] = $version['revision'];
    }
    else {
      $this->version = array(
        'name' => ABLE_POLECAT_VERSION_NAME,
        'major' => ABLE_POLECAT_VERSION_MAJOR,
        'minor' => ABLE_POLECAT_VERSION_MINOR,
        'revision' => ABLE_POLECAT_VERSION_REVISION,
      );
    }
  }
  
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
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Session');
        break;
    }
    return $ValidLink;
  }
  
  /**
   * Shut down Able Polecat server and send HTTP response.
   *
   * @param int $status Return code.
   */
  protected static function shutdown($status = 0) {
    
    if (isset(self::$Host) && isset(self::$Host->Response)) {
      self::$Host->Response->send();
    }
    else {
      require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Error.php')));
      $Resource = AblePolecat_Resource_Error::wakeup();
      $Resource->notice = "Able Polecat server was directed to shut down before generating response to request URI.";
      $Response = AblePolecat_Message_Response::create(500);
      $Response->setEntityBody($Resource);
      $Response->send();
    }
    exit($status);
  }
  
  protected function __construct() {
    
    self::$report_errors = E_ALL;
    self::$display_errors = 0;
    if (isset($_REQUEST['display_errors'])) {
      $display_errors = strval($_REQUEST['display_errors']);
      switch ($display_errors) {
        default:
          self::$display_errors = E_ALL;
          break;
        case 'strict':
          self::$report_errors = E_STRICT;
          self::$display_errors = E_STRICT;
          break;
      }
      
      //
      // Error settings for local development only
      //
      error_reporting(self::$report_errors);
      ini_set('display_errors', self::$display_errors);
    }
    else {
      //
      // Error settings for production web server
      //
      error_reporting(self::$report_errors);
      ini_set('display_errors', self::$display_errors);
    }
    //
    // Default error/exception handling
    //
    set_error_handler(array('AblePolecat_Host', 'handleError'));
    set_exception_handler(array('AblePolecat_Host', 'handleException'));

    //
    // Start or resume session.
    //
    // $this->Session = AblePolecat_Session::wakeup($this);
    //
    // Start or resume session.
    //
    session_start();
    $this->sessionId = session_id();
  
    //
    // Cache session global variable to ensure that it is not used/tampered with by
    // application/user mode.
    //
    $this->sessionGlobal = $_SESSION;
    
    //
    // Turn on output buffering.
    //
    ob_start();
    
    //
    // Initialize other members.
    //
    $this->Request = NULL;
    $this->setVersion(NULL);
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  public function __destruct() {
    //
    // Flush output buffer.
    //
    ob_end_flush();
    
    //
    // Close and save session.
    //
    session_write_close();
  }
}