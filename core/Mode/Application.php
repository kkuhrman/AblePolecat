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
   * @var AblePolecat_Mode_ApplicationAbstract Concrete ApplicationMode instance.
   */
  protected static $ApplicationMode;
    
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
   * Allow given subject to serve as direct subordinate in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended subordinate target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function forwardCommandLink(AblePolecat_Command_TargetInterface $Target) {
    
    $Super = NULL;
    
    //
    // Only user mode can serve as next in COR.
    //
    if (is_a($Target, 'AblePolecat_Mode_User')) {
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
    self::$ApplicationMode = NULL;
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$ApplicationMode)) {
      try {
        //
        // Create instance of application mode
        //
        self::$ApplicationMode = new AblePolecat_Mode_Application($Subject);
        
        //
        // Load environment settings
        //
        self::$ApplicationMode->setEnvironment(AblePolecat_Environment_Application::wakeup($Subject));
      }
      catch(Exception $Exception) {
        self::$ApplicationMode = NULL;
        throw new AblePolecat_Server_Exception(
          'Failed to initialize application mode. ' . $Exception->getMessage(),
          AblePolecat_Error::BOOT_SEQ_VIOLATION
        );
      }
    }
    return self::$ApplicationMode;
  }
}