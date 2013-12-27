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
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));

class AblePolecat_Server extends AblePolecat_AccessControl_Delegate_SystemAbstract implements AblePolecat_Command_TargetInterface {

  //
  // System environment variable names
  //
  const SYSVAR_CORE_VERSION   = 'coreVersion';
  const SYSVAR_CORE_CLASSES   = 'coreClasses';
  const SYSVAR_CORE_DATABASE  = 'coreDatabase';
  const SYSVAR_CORE_INTERFACES = 'coreInterfaces';
  
  //
  // Access control id
  //
  const UUID              = '603a37e0-5dec-11e3-949a-0800200c9a66';
  const NAME              = 'Able Polecat Server';
  
  /**
   * @var AblePolecat_Server Singleton instance.
   */
  private static $Server = NULL;
  
  /**
   * @var AblePolecat_Mode_Server.
   */
  private $Mode;
  
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
   * Execute the command and return the result of the action.
   *
   * @param AblePolecat_CommandInterface $Command The command to execute.
   */
  public function execute(AblePolecat_CommandInterface $Command) {
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
   
  /**
   * A default response if request could not be routed but no error occurred.
   */
  protected function sendDefaultResponse() {
    
    //
    // Create a default response.
    //
    $Response = AblePolecat_Message_Response::create(200);
    
    //
    // Include Able Polecat version info in response body.
    //
    $Response->body = AblePolecat_Message_Response::BODY_DOCTYPE_XML;
    $Response->body .= "<able_polecat>";
    $Response->body .= AblePolecat_Server::getVersion(TRUE, 'XML');
    $Response->body .= "</able_polecat>";
    
    //
    // Send response.
    //
    $Response->send();
  }
  
  /**
   * Main point of entry for all Able Polecat page and service requests.
   *
   */
  public static function routeRequest() {
    
    if (self::bootstrap()) {
      //
      // Route the request.
      //
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
    
    if (isset(self::$Server)) {
      if (isset(self::$Server->Response)) {
        self::$Server->Response->send();
      }
      else {
        self::$Server->sendDefaultResponse();
      }
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
      self::$Server->Mode = AblePolecat_Mode_Server::wakeup(self::$Server);
      
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
    $this->Mode = NULL;
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
