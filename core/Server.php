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
  public function getRequestedResource(AblePolecat_Message_RequestInterface $Request) {
    
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
        select('className')->
        from('resource')->
        where("resourceName = '$resource_name'");      
      $CommandResult = AblePolecat_Command_DbQuery::invoke(self::$Host->CommandChain[self::RING_SERVER_MODE], $sql);
      $className = NULL;
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        isset($classInfo[0]['className']) ? $className = $classInfo[0]['className'] : NULL;
      }
      if (isset($className)) {
        //
        // Resource request resolves to registered class name, try to load.
        //
        $CommandResult = AblePolecat_Command_GetRegistry::invoke(self::$Host->CommandChain[self::RING_SERVER_MODE], 'AblePolecat_Registry_Class');
        if ($CommandResult->success()) {
          //
          // Save reference to class registry.
          //
          $ClassRegistry = $CommandResult->value();
          
          //
          // Get agent from user mode
          //
          $Agent = NULL;
          $CommandResult = AblePolecat_Command_GetAgent::invoke(self::$Host->CommandChain[self::RING_USER_MODE]);
          if ($CommandResult->success()) {
            $Agent = $CommandResult->value();
          }
          
          //
          // Attempt to load resource class
          //
          $Resource = $ClassRegistry->loadClass($className, $Agent);
        }
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
  public function getResponse(AblePolecat_ResourceInterface $Resource) {
    
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
        $Resource = self::$Host->getRequestedResource($Request);
        self::$Host->Response = self::$Host->getResponse($Resource);
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
   * Shut down Able Polecat server and send HTTP response.
   */
  public static function shutdown() {
    
    if (isset(self::$Host) && isset(self::$Host->Response)) {
      self::$Host->Response->send();
    }
    else {
      $backtrace = AblePolecat_Server::getFunctionCallBacktrace(2);
      $body = sprintf("<AblePolecat><error>
        <message>Able Polecat server was directed to shut down before generating response to request URI.</message>
        <file>%s</file>
        <line>%d</line>
        <class>%s</class>
        <function>%s</function>
        </error></AblePolecat>",
        isset($backtrace['file']) ? $backtrace['file'] : '',
        isset($backtrace['line']) ? $backtrace['line'] : 0,
        isset($backtrace['class']) ? $backtrace['class'] : '',
        isset($backtrace['function']) ? $backtrace['function'] : ''
      );
      $Response = AblePolecat_Message_Response::create(200);
      $Response->body = $body;
      $Response->send();
    }
    exit(0);
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
    if (isset($stackPos) && is_scalar($stackPos) && isset($backtrace[$stackPos])) {
      //
      // @todo: this is an uncertain hack to get line # to correspond/sync with function/method and file
      //
      isset($backtrace[$stackPos - 1]['line']) ? $line = $backtrace[$stackPos - 1]['line'] : $line = $backtrace[$stackPos]['line'];
      $backtrace = $backtrace[$stackPos];
      $backtrace['line'] = $line;
    }
    return $backtrace;
  }
      
  /**
   * Initialize resources in protection ring '0' (e.g. kernel).
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // Initiate command-processing chain of responsibility.
    // (each object wakes up it's subordinate down the chain).
    //
    $this->CommandChain = array();
    $this->CommandChain[self::RING_SERVER_MODE] = 
      AblePolecat_Mode_Server::wakeup($this);
    $this->CommandChain[self::RING_APPLICATION_MODE] = 
      AblePolecat_Mode_Application::wakeup($this->CommandChain[self::RING_SERVER_MODE]);
    $this->CommandChain[self::RING_USER_MODE] = 
      AblePolecat_Mode_User::wakeup($this->CommandChain[self::RING_APPLICATION_MODE]);
        
    $this->setVersion(NULL);
    
    $this->Response = NULL;
    $this->Subordinate = NULL;
  }
}