<?php
/**
 * @file: Application.php
 * Base class for Application modes (second most protected).
 */

/**
 * Configurable paths are defined *after* server conf file is loaded.
 * Any use prior to this must use AblePolecat_Server_Paths::getFullPath().
 * This is best practice in any case rather than using global constants .
 */
 
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Mode.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Application.php')));

class AblePolecat_Mode_Application extends AblePolecat_ModeAbstract {

  /**
   * Constants.
   */
  const UUID = 'b306fe20-5f4c-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Application Mode';
  const RESOURCE_ALL = 'all';
  
  /**
   * @var AblePolecat_Mode_ApplicationAbstract Concrete Mode instance.
   */
  protected static $Mode;
    
  /**
   * @var List of interfaces which can be used as application resources.
   */
  private static $supported_interfaces = NULL;
  
  /**
   * @var Array $Resources.
   *
   * Application resources are stored as Array([type] => [module name]).
   */
  private $Resources = NULL;
  
  /********************************************************************************
   * Access control methods.
   ********************************************************************************/
   
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
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
      case 'forward':
        $ValidLink = is_a($Target, 'AblePolecat_Mode_User');
        break;
      case 'reverse':
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Server');
        break;
    }
    return $ValidLink;
  }
  
  /********************************************************************************
   * Resource access methods.
   ********************************************************************************/
  
  /********************************************************************************
   * Caching methods.
   ********************************************************************************/
   
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    //
    // Supported Able Polecat interfaces.
    //
    $this->Resources = array();
    $supported_resources = self::getSupportedResourceInterfaces();
    foreach($supported_resources as $key => $interface_name) {
      $this->Resources[$interface_name] = array();
    }
    
    //
    // Check for required server resources.
    // (these will throw exception if not ready).
    //
    AblePolecat_Server::getBootMode();
    AblePolecat_Server::getClassRegistry();
    AblePolecat_Server::getDefaultLog();
    AblePolecat_Server::getServerMode();
    AblePolecat_Server::getServiceBus();
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
    self::$Mode = NULL;
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Mode)) {
      try {
        //
        // Create instance of application mode
        //
        self::$Mode = new AblePolecat_Mode_Application($Subject);
        
        //
        // Set chain of responsibility relationship
        //
        $Subject->setForwardCommandLink(self::$Mode);
        self::$Mode->setReverseCommandLink($Subject);
        
        //
        // @todo: load environment settings in initialize().
        //
        // self::$Mode->setEnvironment(AblePolecat_Environment_Application::wakeup($Subject));
      }
      catch(Exception $Exception) {
        self::$Mode = NULL;
        throw new AblePolecat_Server_Exception(
          'Failed to initialize application mode. ' . $Exception->getMessage(),
          AblePolecat_Error::BOOT_SEQ_VIOLATION
        );
      }
    }
    return self::$Mode;
  }
}