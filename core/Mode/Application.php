<?php
/**
 * @file      polecat/core/Mode/Application.php
 * @brief     Base class for Application modes (second most protected).
 *
 * Provide support for loading Able Polecat extension code.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));

class AblePolecat_Mode_Application extends AblePolecat_ModeAbstract {

  /**
   * Constants.
   */
  const UUID = 'b306fe20-5f4c-11e3-949a-0800200c9a66';
  const NAME = 'AblePolecat_Mode_Application';
  const RESOURCE_ALL = 'all';
  
  /**
   * Names of supported application interfaces.
   */
  const APP_INTERFACE_COMMAND_TARGET      = 'AblePolecat_Command_TargetInterface';
  const APP_INTERFACE_DATABASE            = 'AblePolecat_DatabaseInterface';
  
  /**
   * @var Instance of Singleton.
   */
  private static $ApplicationMode;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $ApplicationEnvironment;
  
  /**
   * @var AblePolecat_Registry_ClassLibrary
   */
  private $ClassLibraryRegistry;
        
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_SubjectInterface.
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
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$ApplicationMode)) {
      //
      // Create instance of session mode
      //
      self::$ApplicationMode = new AblePolecat_Mode_Application();
    }
      
    return self::$ApplicationMode;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Command_TargetInterface.
   ********************************************************************************/
   
  /**
   * Execute a command or pass back/forward chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public function execute(AblePolecat_CommandInterface $Command) {
    
    $Result = NULL;
    
    //
    // @todo: check invoker access rights
    //
    switch ($Command::getId()) {
      default:
        //
        // Not handled
        //
        break;
    }
    //
    // Pass command to next link in chain of responsibility
    //
    $Result = $this->delegateCommand($Command, $Result);
    return $Result;
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
        $ValidLink = is_a($Target, 'AblePolecat_Host');
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Server');
        break;
    }
    return $ValidLink;
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() { 
    
    parent::initialize();
    
    //
    // Initiate server mode and establish as reverse command target.
    //
    $CommandChain = AblePolecat_Command_Chain::wakeup();
    $ServerMode = AblePolecat_Mode_Server::wakeup();
    $CommandChain->setCommandLink($ServerMode, $this);
    
    //
    // Load class registry.
    //
    $ClassRegistry = AblePolecat_Registry_Class::wakeup($this->getAgent());
    
    //
    // Load environment/configuration
    //
    //
    $this->ApplicationEnvironment = AblePolecat_Environment_Application::wakeup($this->getAgent($this));
    if (isset($ClassRegistry)) {
      //
      // Register classes in registered libraries.
      //
      $ClassLibraryRegistrations = $this->ApplicationEnvironment->
        getVariable($this->getAgent($this), AblePolecat_Environment_Application::SYSVAR_CORE_CLASSLIBS);
      if (isset($ClassLibraryRegistrations)) {
        foreach($ClassLibraryRegistrations as $key => $ClassLibraryRegistration) {
          //
          // Boot log can help with troubleshooting third party library installs.
          //
          $message = sprintf("REGISTRY: %s class library (id=%s)",
            $ClassLibraryRegistration->getName(),
            $ClassLibraryRegistration->getId()
          );
          AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
          
          //
          // Register library classes.
          //
          $ClassRegistrations = $ClassRegistry->loadLibrary($ClassLibraryRegistration->getId());
        }
      }
    }
    
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Application(s) mode is initialized.');
  }
}