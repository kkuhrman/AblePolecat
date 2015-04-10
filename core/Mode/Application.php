<?php
/**
 * @file      polecat/core/Mode/Application.php
 * @brief     Base class for Application modes (second most protected).
 *
 * Provide support for loading Able Polecat extension code.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
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
   * Implementation of AblePolecat_ModeInterface.
   ********************************************************************************/
  
  /**
   * Returns assigned value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   *
   * @return mixed Assigned value of given variable or NULL.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function getEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name) {
    
    $VariableValue = NULL;
    if (isset(self::$ApplicationMode) && isset(self::$ApplicationMode->ApplicationEnvironment)) {
      $VariableValue = self::$ApplicationMode->ApplicationEnvironment->getVariable($Agent, $name);
    }
    else {
      throw new AblePolecat_Mode_Exception("Cannot access variable '$name'. Environment is not initialized.");
    }
    return $VariableValue;
  }
  
  /**
   * Assign value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   * @param mixed $value Value of variable.
   *
   * @return bool TRUE if variable is set, otherwise FALSE.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function setEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name, $value) {
    $VariableSet = NULL;
    if (isset(self::$ApplicationMode) && isset(self::$ApplicationMode->ApplicationEnvironment)) {
      $VariableSet = self::$ApplicationMode->ApplicationEnvironment->setVariable($Agent, $name, $value);
    }
    else {
      throw new AblePolecat_Mode_Exception("Cannot access variable '$name'. Environment is not initialized.");
    }
    return $VariableSet;
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
    // Load environment/configuration
    //
    //
    $this->ApplicationEnvironment = AblePolecat_Environment_Application::wakeup($this->getAgent($this));
    
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Application(s) mode is initialized.');
  }
}