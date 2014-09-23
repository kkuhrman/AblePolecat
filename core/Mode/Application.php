<?php
/**
 * @file      polecat/core/Mode/Application.php
 * @brief     Base class for Application modes (second most protected).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'ClassLibrary.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));

abstract class AblePolecat_Mode_ApplicationAbstract extends AblePolecat_Mode_ServerAbstract {

  /**
   * Constants.
   */
  const UUID = 'b306fe20-5f4c-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Application Mode';
  const RESOURCE_ALL = 'all';
  
  /**
   * Names of supported application interfaces.
   */
  const APP_INTERFACE_COMMAND_TARGET      = 'AblePolecat_Command_TargetInterface';
  const APP_INTERFACE_DATABASE            = 'AblePolecat_DatabaseInterface';
  
  /**
   * @var AblePolecat_AccessControl_Agent_Application
   */
  private $ApplicationAgent;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $ApplicationEnvironment;
  
  /**
   * @var AblePolecat_Registry_ClassLibrary
   */
  private $ClassLibraryRegistry;
  
  /**
   * @var Array Container for instances of application interfaces.
   */
  private $ApplicationInterfaces;
      
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
  
  /********************************************************************************
   * Implementation of AblePolecat_Command_TargetInterface.
   ********************************************************************************/
   
  /**
   * Execute a command or pass back/forward chain of responsibility.
   *
   * Application Mode will give contributed command targets a chance to handle a 
   * command before passing it back on the CoR. This implementation of the execute()
   * method will make a 'detour' on the CoR by passing the command to the lowest 
   * ranking member in the application command target hierarchy, thereby providing 
   * the ability to 'hook into' the command processing CoR.
   *
   * So, the CoR is ordered:
   * First, user mode
   * Second, application mode
   * Third, application (contributed) targets
   * Fourth, server mode
   * Last, server
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
        break;
      case 'ef797050-715c-11e3-981f-0800200c9a66':
        //
        // DbQuery
        //
        $message = 'SQL from ' . $Command->getInvoker()->getName() . ' ' . $Command->getArguments()->__toString();
        AblePolecat_Command_Log::invoke($Command->getInvoker(), $message, AblePolecat_LogInterface::INFO);
        // $QueryResult = $this->executeDbQuery($Command->getArguments());
        // $Result = new AblePolecat_Command_Result($QueryResult, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        break;
      case '85fc7590-724d-11e3-981f-0800200c9a66':
        //
        // Log
        //
        switch($Command->getEventSeverity()) {
          default:
            break;
          case AblePolecat_LogInterface::DEBUG:
          case AblePolecat_LogInterface::APP_INFO:
          case AblePolecat_LogInterface::APP_STATUS:
          case AblePolecat_LogInterface::APP_WARNING:
            //
            // Do not pass these to next link in CoR.
            //
            $Result = new AblePolecat_Command_Result(NULL, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
            break;
        }
        break;
    }
        
    //
    // Application command targets are not ordered in a hierarchy.
    // They are given a chance to hook into command based on their
    // order of being registered.
    //
    foreach($this->ApplicationInterfaces[self::APP_INTERFACE_COMMAND_TARGET] as $targetClassName => $Target) {
      //
      // @todo: handle accumulated command execution results
      //
      $SubResult = $Target->execute($Command);
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
   * @return AblePolecat_AccessControl_Agent_Application
   */
  private function getAgent() {
    if (!isset($this->ApplicationAgent)) {
      throw new AblePolecat_Mode_Exception('Application agent is not available.');
    }
    return $this->ApplicationAgent;
  }
  
  /**
   * Send command or forward or back the chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   * @param AblePolecat_Command_Result $Result Optional, do delegate if set.
   *
   * @return AblePolecat_Command_Result
   */
  protected function delegateCommand(AblePolecat_CommandInterface $Command, AblePolecat_Command_Result $Result = NULL) {
    if (is_a($Command, 'AblePolecat_Command_ForwardInterface')) {
      parent::delegateCommand($Command, $Result);
    }
    else if (!isset($Result)) {
      $Result = parent::execute($Command);
    }
    return $Result;
  }
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    parent::initialize();
    
    $this->ApplicationInterfaces = array(
      self::APP_INTERFACE_COMMAND_TARGET => array(),
      self::APP_INTERFACE_DATABASE => array(),
    );
    
    //
    // Access control agent.
    //
    $Host = $this->getReverseCommandLink();
    $this->ApplicationAgent = AblePolecat_AccessControl_Agent_Application::wakeup($Host, $this);
    
    //
    // Load environment/configuration
    //
    //
    $this->ApplicationEnvironment = AblePolecat_Environment_Application::wakeup($this->ApplicationAgent);
    $ClassRegistry = $this->getClassRegistry();
    if (isset($ClassRegistry)) {
      //
      // Register classes in registered libraries.
      //
      $ClassLibraryRegistrations = $this->ApplicationEnvironment->
        getVariable($this->ApplicationAgent, AblePolecat_Environment_Application::SYSVAR_CORE_CLASSLIBS);
      if (isset($ClassLibraryRegistrations)) {
        foreach($ClassLibraryRegistrations as $key => $ClassLibraryRegistration) {
          $ClassRegistrations = $ClassRegistry->loadLibrary($ClassLibraryRegistration->getClassLibraryId());
        }
      }
    }
            
    //
    // Load registry of class libraries.
    //
    // $this->ClassLibraryRegistry = AblePolecat_Registry_ClassLibrary::wakeup($this);
    
    //
    // Load application command targets.
    //
    // $CommandResult = AblePolecat_Command_GetRegistry::invoke($Subject, 'AblePolecat_Registry_Class');
    // $Registry = $CommandResult->value();
    // if (isset($Registry)) {
      // $CommandTargets = $Registry->getClassListByKey(AblePolecat_Registry_Class::KEY_INTERFACE, 'AblePolecat_Command_TargetInterface');
      // foreach ($CommandTargets as $className => $classInfo) {
        // $this->ApplicationInterfaces[self::APP_INTERFACE_COMMAND_TARGET][$className] = $Registry->loadClass($className);
      // }
    // }
    
    //
    // @todo: Load application database(s).
    //
    
    AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'Application(s) mode is initialized.');
  }
}