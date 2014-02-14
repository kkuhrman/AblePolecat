<?php
/**
 * @file: Server.php
 * Server as in 'client-server' and also as in $_SERVER[].
 *
 * Server has the following duties:
 * 1. Marshall web server REQUEST
 * 2. Initiate chain of responsibility (COR - server, application, user, etc)
 * 3. Dispatch marshalled request object
 * 4. Unmarshall RESPONSE, send HTTP response head/body
 * 5. Handle shut down and redirection in the event of error
 * 6. Act as terminal/final command target
 * 7. Act as binding access control arbitrator
 */

/**
 * Most current version is loaded from conf file. These are defaults.
 */
define('ABLE_POLECAT_VERSION_NAME', 'DEV-0.4.0');
define('ABLE_POLECAT_VERSION_ID', 'ABLE_POLECAT_CORE_0_4_0_DEV');
define('ABLE_POLECAT_VERSION_MAJOR', '0');
define('ABLE_POLECAT_VERSION_MINOR', '4');
define('ABLE_POLECAT_VERSION_REVISION', '0');

/**
 * Request query string parameter.
 */
define('ABLE_POLECAT_BOOT_DIRECTIVE', 'mode');

/**
 * Root directory of the entire Able Polecat core project.
 */
if (!defined('ABLE_POLECAT_ROOT')) {
  $ABLE_POLECAT_ROOT = dirname(__DIR__);
  define('ABLE_POLECAT_ROOT', $ABLE_POLECAT_ROOT);
}

/**
 * Location of Able Polecat core class library.
 */
if (!defined('ABLE_POLECAT_CORE')) {
  $ABLE_POLECAT_CORE = __DIR__;
  define('ABLE_POLECAT_CORE', $ABLE_POLECAT_CORE);
}

/**
 * Location of directory with host-specific system-wide configuration file(s).
 */
if (!defined('ABLE_POLECAT_ETC')) {
  $ABLE_POLECAT_ETC = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'etc';
  define('ABLE_POLECAT_ETC', $ABLE_POLECAT_ETC);
}

/**
 * Variable files directory (e.g. log files).
 */
if (!defined('ABLE_POLECAT_FILES')) {
  $ABLE_POLECAT_FILES = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'files';
  define('ABLE_POLECAT_FILES', $ABLE_POLECAT_FILES);
}

/**
 * Secondary directory hierarchy contains third-party modules, custom pages, services, 
 * utilities, etc.
 */
if (!defined('ABLE_POLECAT_USR')) {
  $ABLE_POLECAT_USR = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'usr';;
  define('ABLE_POLECAT_USR', $ABLE_POLECAT_USR);
}

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Delegate', 'System.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Template.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'User.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Bus.php')));

class AblePolecat_Server extends AblePolecat_AccessControl_Delegate_SystemAbstract implements AblePolecat_Command_TargetInterface {

  //
  // System environment variable names
  //
  const SYSVAR_CORE_VERSION     = 'coreVersion';
  const SYSVAR_CORE_CLASSES     = 'coreClasses';
  const SYSVAR_CORE_DATABASE    = 'coreDatabase';
  const SYSVAR_CORE_INTERFACES  = 'coreInterfaces';
  
  //
  // Ring constants, like OS protection rings, define chain of responsibility hierarchy
  //
  const RING_SERVER_MODE        = 0;
  const RING_APPLICATION_MODE   = 1;
  const RING_USER_MODE          = 2;
  
  //
  // Access control id
  //
  const UUID                    = '603a37e0-5dec-11e3-949a-0800200c9a66';
  const NAME                    = 'Able Polecat Server';
  
  /**
   * @var AblePolecat_Server Singleton instance.
   */
  private static $Server = NULL;
  
  /**
   * @var AblePolecat_Command_TargetInterface.
   */
  private $CommandChain;
  
  /**
   * @var AblePolecat_Message_ResponseInterface.
   */
  private $Response;
  
  /**
   * @var Next forward target in command chain of responsibility.
   */
  private $Subordinate;
  
  /**
   * @var string Version number from server config settings file.
   */
  private $version;
  
  /********************************************************************************
   * Access control methods.
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
   * Command target methods.
   ********************************************************************************/
  
  /**
   * Request to server to issue command to chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public static function dispatchCommand(AblePolecat_CommandInterface $Command) {
    
    $Result = NULL;
    
    $direction = NULL;
    if (is_a($Command, 'AblePolecat_Command_ForwardInterface')) {
      $direction = AblePolecat_Command_TargetInterface::CMD_LINK_FWD;
    }
    if (is_a($Command, 'AblePolecat_Command_ReverseInterface')) {
      $direction = AblePolecat_Command_TargetInterface::CMD_LINK_REV;
    }
    
    if (isset(self::$Server)) {
      switch ($direction) {
        default:
          break;
        case AblePolecat_Command_TargetInterface::CMD_LINK_FWD:
          $Result = self::$Server->execute($Command);
          break;
        case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
          $Target = self::$Server;
          if ($key = count(self::$Server->CommandChain)) {
            $key = $key - 1;
            isset(self::$Server->CommandChain[$key]) ? $Target = self::$Server->CommandChain[$key] : NULL;
          }
          $Result = $Target->execute($Command);
          break;
      }
    }
    return $Result;
  }
  
   /**
   * Execute a command or pass forward on chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public function execute(AblePolecat_CommandInterface $Command) {
    
    $Result = NULL;
    
    $direction = NULL;
    if (is_a($Command, 'AblePolecat_Command_ForwardInterface')) {
      $direction = AblePolecat_Command_TargetInterface::CMD_LINK_FWD;
    }
    if (is_a($Command, 'AblePolecat_Command_ReverseInterface')) {
      $direction = AblePolecat_Command_TargetInterface::CMD_LINK_REV;
    }
    
    switch ($Command::getId()) {
      default:
        if ($direction === AblePolecat_Command_TargetInterface::CMD_LINK_FWD) {
          if (isset($this->CommandChain[self::RING_SERVER_MODE])) {
            $Result = $this->CommandChain[self::RING_SERVER_MODE]->execute($Command);
          }
        }
        break;
      case '85fc7590-724d-11e3-981f-0800200c9a66':
        //
        // Apparently, no other log facility was available to handle the message
        //
        AblePolecat_Log_Syslog::wakeup()->putMessage($Command->getEventSeverity(), $Command->getEventMessage());
        break;
    }
    
    //
    // If STILL no result, we've reached the end of the line. Return FAIL.
    // 
    if (!isset($Result)) {
      $Result = new AblePolecat_Command_Result();
    }
    return $Result;
  }
  
  /**
   * Allow given subject to serve as direct subordinate in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended subordinate target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setForwardCommandLink(AblePolecat_Command_TargetInterface $Target) {
    
    $Super = NULL;
    
    //
    // Only server mode can serve as next in COR.
    //
    if (is_a($Target, 'AblePolecat_Mode_Server')) {
      $Super = $this;
      $this->Subordinate = $Target;
    }
    else {
      $msg = sprintf("Attempt to set %s as forward command link to %s was refused.",
        get_class($Target),
        get_class($this)
      );
      throw new AblePolecat_Command_Exception($msg);
    }
    return $Super;
  }
  
  /**
   * Allow given subject to serve as direct superior in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended superior target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setReverseCommandLink(AblePolecat_Command_TargetInterface $Target) {
    
    $msg = sprintf("%s must be highest node in any chain of responsibility hierarchy.",
      get_class($this)
    );
    throw new AblePolecat_Command_Exception($msg);
  }
  
  /********************************************************************************
   * HTTP request/response methods.
   ********************************************************************************/
  /********************************************************************************
   * Sends HTML status page if requested or otherwise if request cannot be routed.
   *
   * Able Polecat is not designed to intended to serve up web pages. However, there
   * will be instances where it is necessary to display *something* more than XML
   * without style rules; for example, something goes wrong during an installation 
   * or update.
   *
   * @param mixed $content Content of status page.
   * 
   ********************************************************************************/
   
  protected function sendDefaultResponse($content = NULL) {
    
    $Response = NULL;
    
    if (isset(self::$Server) && isset(self::$Server->CommandChain[self::RING_SERVER_MODE])) {
      //
      // Create an array of data to be inserted into template
      //
      $dbState = self::$Server->CommandChain[self::RING_SERVER_MODE]->getDatabaseState(self::$Server);
      $dbState['connected'] ? $dbStateStr = 'Connected to ' : $dbStateStr = 'Not connected to ';
      $dbStateStr .= $dbState['name'] . ' database.';
      
      $substitutions = array(
        'POLECAT_VERSION' => AblePolecat_Server::getVersion(TRUE, 'HTML'),
        'POLECAT_DBSTATE' => $dbStateStr,
        
      );
      
      //
      // Load response template
      //
      self::$Server->Response = AblePolecat_Message_Response_Template::create(
        self::$Server->CommandChain[self::RING_SERVER_MODE],
        AblePolecat_Message_Response_Template::DEFAULT_STATUS,
        $substitutions
      );
      
      if (isset(self::$Server->CommandChain[self::RING_USER_MODE])) {
        AblePolecat_Command_Log::invoke(self::$Server->CommandChain[self::RING_USER_MODE], 'status page requested', 'debug');
      }
      
      //
      // Shutdown and send response.
      //
      self::shutdown();
    }
    else {
      $Response = AblePolecat_Message_Response::create(200);
      $Response->body = "<error><message>Able Polecat server is being directed to send HTTP response but has failed to initialize 
        or has already shut down.</message><content>$content</content></error>";      
      $Response->send();
      exit(1);
    }
  }
  
  /**
   * Main point of entry for all Able Polecat page and service requests.
   *
   */
  public static function routeRequest() {
    
    if (self::bootstrap()) {
      //
      // Build request.
      //
      $Request = NULL;
      isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
      switch ($method) {
        default:
          break;
        case 'GET':
          $Request = AblePolecat_Message_Request_Get::create();
          break;
        case 'POST':
          $Request = AblePolecat_Message_Request_Post::create();
          break;
        case 'PUT':
          $Request = AblePolecat_Message_Request_Put::create();
          break;
        case 'DELETE':
          $Request = AblePolecat_Message_Request_Delete::create();
          break;
      }
      
      if (!isset($Request)) {
        //
        // @todo: 405 Method not allowed.
        // The method specified in the Request-Line is not allowed for the resource identified by the Request-URI. 
        // The response MUST include an Allow header containing a list of valid methods for the requested resource. 
        //
      }
      else {
        $requestPathInfo = $Request->getResource()->getRequestPathInfo();
        switch ($requestPathInfo[AblePolecat_Url::URI_RESOURCE_NAME]) {
          default:
            //
            // @todo: get agent from user mode
            //
            $Agent = NULL;
            $CommandResult = AblePolecat_Command_GetAgent::invoke(self::$Server->CommandChain[self::RING_USER_MODE]);
            if ($CommandResult->success()) {
              $Agent = $CommandResult->value();
            }
            
            //
            // Dispatch the request
            //
            $ServiceBus = AblePolecat_Service_Bus::wakeup(self::$Server->CommandChain[self::RING_SERVER_MODE]);
            self::$Server->Response = $ServiceBus->dispatch($Agent, $Request);
            break;
          case AblePolecat_Url::URI_SLASH:
            self::$Server->sendDefaultResponse();
            break;
        }
      }
    }
    else {
      //
      // Only one call per HTTP request.
      //
      throw new AblePolecat_Server_Exception(
        'Able Polecat server is already routing the current request.',
        AblePolecat_Error::ACCESS_INVALID_OBJECT
      );
    }
    
    //
    // shut down and send response
    //
    AblePolecat_Server::shutdown();
  }
  
  /**
   * Shut down Able Polecat server and send HTTP response.
   */
  public static function shutdown() {
    
    $ResponseSent = FALSE;
    
    if (isset(self::$Server)) {
      if (isset(self::$Server->Response)) {
        self::$Server->Response->send();
        $ResponseSent = TRUE;
      }
    }
    if (!$ResponseSent) {
      $Response = AblePolecat_Message_Response::create(200);
      $Response->body = "<errorMessage>Able Polecat server is being directed to shut down but has failed to generate a response to last request.</errorMessage>";
      $Response->send();
    }
    exit(0);
  }
  
  /********************************************************************************
   * Server bootstrap procedures
   ********************************************************************************/
  
  /**
   * Main bootstrap routine.
   */
  protected static function bootstrap() {
    
    $Ready = FALSE;
    
    //
    // AblePolecat_Server implements Singelton design pattern.
    //
    if (!isset(self::$Server)) {
      //
      // Create instance of Singleton.
      //
      self::$Server = new AblePolecat_Server();

      //
      // Initiate command-processing chain of responsibility.
      // (each object wakes up it's subordinate down the chain).
      //
      self::$Server->CommandChain[self::RING_SERVER_MODE] = AblePolecat_Mode_Server::wakeup(self::$Server);
      self::$Server->CommandChain[self::RING_APPLICATION_MODE] = 
        AblePolecat_Mode_Application::wakeup(self::$Server->CommandChain[self::RING_SERVER_MODE]);
      self::$Server->CommandChain[self::RING_USER_MODE] = 
        AblePolecat_Mode_User::wakeup(self::$Server->CommandChain[self::RING_APPLICATION_MODE]);
      
      //
      // Ready to route HTTP request.
      //
      $Ready = TRUE;
    }
    return $Ready;
  }
  
  /********************************************************************************
   * Sysinfo methods.
   ********************************************************************************/
  
  /**
   * Sets version information from core configuration file.
   */
  protected function setVersion($version = NULL) {
    
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
   * Get version number of server/core.
   */
  public static function getVersion($as_str = TRUE, $doc_type = 'XML') {
    
    $version = NULL;
    
    //
    // @todo: override defaults with data from core conf file?
    //
    if (isset(self::$Server->version)) {
      if ($as_str) {
        switch ($doc_type) {
          default:
            $version = sprintf("Version %s.%s.%s (%s)",
              self::$Server->version['major'],
              self::$Server->version['minor'],
              self::$Server->version['revision'],
              self::$Server->version['name']
            );
            break;
          case 'XML':
            $version = sprintf("<polecat_version name=\"%s\"><major>%s</major><minor>%s</minor><revision>%s</revision></polecat_version>",
              self::$Server->version['name'],
              strval(self::$Server->version['major']),
              strval(self::$Server->version['minor']),
              strval(self::$Server->version['revision'])
            );
            break;
          //
          // @todo: case 'JSON':
          //
        }
      }
      else {
        $version = self::$Server->version;
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
  
  /********************************************************************************
   * Create/destroy methods
   ********************************************************************************/
  
  /**
   * Initialize resources in protection ring '0' (e.g. kernel).
   */
  protected function initialize() {
    //
    // Set defaults.
    //
    $this->setVersion(NULL);
    $this->CommandChain = array();
    $this->Response = NULL;
    $this->Subordinate = NULL;
  }
  
  final protected function __construct() {
    
    //
    // Turn on output buffering.
    //
    ob_start();
    
    //
    // Not ready until after initialize().
    //
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    //
    // Flush output buffer.
    //
    ob_end_flush();
  }
}
