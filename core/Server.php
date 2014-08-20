<?php
/**
 * @file      polecat/core/Server.php
 * @brief     Routes HTTP(S) request and returns response.
 *
 * Server has the following duties:
 * 1. Marshall web server REQUEST
 * 2. Initiate chain of responsibility (COR - server, application, user, etc)
 * 3. Dispatch marshalled request object
 * 4. Unmarshall RESPONSE, send HTTP response head/body
 * 5. Handle shut down and redirection in the event of error
 * 6. Act as terminal/final command target
 * 7. Act as binding access control arbitrator
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

/**
 * Most current version is loaded from conf file. These are defaults.
 */
define('ABLE_POLECAT_VERSION_NAME', 'DEV-0.6.0');
define('ABLE_POLECAT_VERSION_ID', 'ABLE_POLECAT_CORE_0_6_0_DEV');
define('ABLE_POLECAT_VERSION_MAJOR', '0');
define('ABLE_POLECAT_VERSION_MINOR', '6');
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

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Administrator.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Host.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Template.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'User.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Bus.php')));

class AblePolecat_Server extends AblePolecat_HostAbstract implements AblePolecat_Command_TargetInterface {

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
   * @var AblePolecat_AccessControl_Agent_Administrator.
   */
  private $Administrator;
  
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
      case 'bed41310-2174-11e4-8c21-0800200c9a66':
        //
        // Check if given agent has requested permission for given resource.
        //
        if ($this->getAdministrator()->hasPermission($this, $Command->getAgentId(), $Command->getResourceId(), $Command->getConstraintId())) {
          //
          // @todo: Access is permitted. Get security token.
          //
          $SecurityToken = '@todo';
          $Result = new AblePolecat_Command_Result($SecurityToken, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        }
        else {
          $Result = new AblePolecat_Command_Result(NULL, AblePolecat_Command_Result::RESULT_RETURN_FAIL);
        }
        break;
      case 'c7587ad0-74a4-11e3-981f-0800200c9a66':
      case 'ef797050-715c-11e3-981f-0800200c9a66':
        //
        // A few reverse type commands must be forward-delegated to server mode by server.
        // This is a security hack so only server itself can directly wakeup Access Control.
        //
        if (isset($this->CommandChain[self::RING_SERVER_MODE])) {
          $Result = $this->CommandChain[self::RING_SERVER_MODE]->execute($Command);
        }
        break;
      case '54d2e7d0-77b9-11e3-981f-0800200c9a66':
        $Agent = $this->getAdministrator()->getAgent($Command->getInvoker());
        $Result = new AblePolecat_Command_Result($Agent, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        break;
      case '7ca0f570-1f22-11e4-8c21-0800200c9a66':
        //
        // Command to shut down indicates abnormal termination
        //
        $body = sprintf("<AblePolecat><notice>Able Polecat shut down unexpectedly.</notice>%s%s</AblePolecat>", 
          $Command->getReason(),
          $Command->getMessage()
        );
        self::$Host->Response = AblePolecat_Message_Response::create(200);
        self::$Host->Response->body = $body;
        self::shutdown($Command->getStatus());
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
   * Implementation of AblePolecat_HostInterface
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
      self::$Host = new AblePolecat_Server();
      
      //
      // @todo: special case no db connection
      //
      
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
      $CommandResult = AblePolecat_Command_DbQuery::invoke(self::$Host->CommandChain[self::RING_SERVER_MODE], $sql);
      
      
      //
      // Map the request path to a specific resource (model)...
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
        $Agent = NULL;
        $CommandResult = AblePolecat_Command_GetAgent::invoke(self::$Host->CommandChain[self::RING_USER_MODE]);
        if ($CommandResult->success()) {
          $Agent = $CommandResult->value();
        }
        self::$Host->Response = AblePolecat_Service_Bus::wakeup(self::$Host->CommandChain[self::RING_USER_MODE])->
          dispatch($Agent, $Request);
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
   * Checks if the requested resource is a valid name in the system.
   *
   * @param string $requestedResourceName Name of requested resource.
   *
   * @return string Name of valid resource (default is 'search').
   */
  public function validateResourceName($requestedResourceName) {
    return $requestedResourceName;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
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
      $backtrace = AblePolecat_Server::getFunctionCallBacktrace('xml');
      $body = sprintf("<AblePolecat><error>
        <message>Able Polecat server was directed to shut down before generating response to request URI.</message>
        %s
        </error></AblePolecat>",
        $backtrace
      );
      // $backtrace = AblePolecat_Server::getFunctionCallBacktrace(2);
      // $body = sprintf("<AblePolecat><error>
        // <message>Able Polecat server was directed to shut down before generating response to request URI.</message>
        // <file>%s</file>
        // <line>%d</line>
        // <class>%s</class>
        // <function>%s</function>
        // </error></AblePolecat>",
        // isset($backtrace['file']) ? $backtrace['file'] : '',
        // isset($backtrace['line']) ? $backtrace['line'] : 0,
        // isset($backtrace['class']) ? $backtrace['class'] : '',
        // isset($backtrace['function']) ? $backtrace['function'] : ''
      // );
      $Response = AblePolecat_Message_Response::create(200);
      $Response->body = $body;
      $Response->send();
    }
    exit($status);
  }
  
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
    
    if (isset(self::$Host)) {
      switch ($direction) {
        default:
          break;
        case AblePolecat_Command_TargetInterface::CMD_LINK_FWD:
          $Result = self::$Host->execute($Command);
          break;
        case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
          $Target = self::$Host;
          if ($key = count(self::$Host->CommandChain)) {
            $key = $key - 1;
            isset(self::$Host->CommandChain[$key]) ? $Target = self::$Host->CommandChain[$key] : NULL;
          }
          $Result = $Target->execute($Command);
          break;
      }
    }
    return $Result;
  }
    
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
   * Debug information helper.
   */
  public static function getFunctionCallBacktrace($stackPos = NULL) {
    $backtrace = debug_backtrace();
    if (isset($stackPos) && isset($backtrace[$stackPos])) {
      //
      // @todo: this is an uncertain hack to get line # to correspond/sync with function/method and file
      //
      isset($backtrace[$stackPos - 1]['line']) ? $line = $backtrace[$stackPos - 1]['line'] : $line = $backtrace[$stackPos]['line'];
      $backtrace = $backtrace[$stackPos];
      $backtrace['line'] = $line;
    }
    else if (isset($stackPos) && ($stackPos == 'xml')) {
      $backtrace_xml = '<backtrace>';
      foreach($backtrace as $key => $frame) {
        $backtrace_xml .= sprintf("<frame id=\"%d\">", $key);
        isset($frame['file']) ? $backtrace_xml .= sprintf("<file>%s</file>", $frame['file']) : NULL;
        isset($frame['line']) ? $backtrace_xml .= sprintf("<line>%d</line>", $frame['line']) : NULL;
        isset($frame['class']) ? $backtrace_xml .= sprintf("<class>%s</class>", $frame['class']) : NULL;
        isset($frame['function']) ? $backtrace_xml .= sprintf("<function>%s</function>", $frame['function']) : NULL;
        $backtrace_xml .= '</frame>';
      }
      $backtrace_xml .= '</backtrace>';
      $backtrace = $backtrace_xml;
    }
    return $backtrace;
  }
  
  /**
   * @return AblePolecat_AccessControl_Agent_Administrator
   */
  protected function getAdministrator() {
    return $this->Administrator;
  }
      
  /**
   * Initialize resources in protection ring '0' (e.g. kernel).
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // Initiate lowest link in command-processing chain of responsibility.
    //
    $this->CommandChain = array();
    $this->CommandChain[self::RING_SERVER_MODE] = 
      AblePolecat_Mode_Server::wakeup($this);
    
    //
    // Server mode restores connection to database, which is needed to 
    // restore session state.
    //
    $this->Administrator = AblePolecat_AccessControl_Agent_Administrator::wakeup($this);
    
    //
    // Initiate rest of CoR (each object wakes up it's subordinate down the chain).
    //
    $this->CommandChain[self::RING_APPLICATION_MODE] = 
      AblePolecat_Mode_Application::wakeup($this->CommandChain[self::RING_SERVER_MODE]);
    $this->CommandChain[self::RING_USER_MODE] = 
      AblePolecat_Mode_User::wakeup($this->CommandChain[self::RING_APPLICATION_MODE]);
        
    $this->setVersion(NULL);
    
    $this->Response = NULL;
    $this->Subordinate = NULL;
  }
}