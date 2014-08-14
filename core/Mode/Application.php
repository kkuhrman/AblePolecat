<?php
/**
 * @file      polecat/core/Mode/Application.php
 * @brief     Base class for Application modes (second most protected).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Application.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'ClassLibrary.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Mode.php');

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
   * @var AblePolecat_AccessControl_AgentInterface
   */
  private $Agent;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $Environment;
  
  /**
   * @var AblePolecat_Registry_ClassLibrary
   */
  private $ClassLibraryRegistry;
  
  /**
   * @var AblePolecat_Command_TargetInterface.
   */
  private $CommandChain;
      
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
        // Access control agent (super user).
        //
        self::$Mode->Agent = AblePolecat_AccessControl_Agent_Application::wakeup(self::$Mode);
        
        //
        // Load environment/configuration
        //
        //
        self::$Mode->Environment = AblePolecat_Environment_Application::wakeup(self::$Mode->Agent);
                
        //
        // Load registry of class libraries.
        //
        self::$Mode->ClassLibraryRegistry = AblePolecat_Registry_ClassLibrary::wakeup(self::$Mode);
        
        //
        // Load application command targets.
        //
        $CommandResult = AblePolecat_Command_GetRegistry::invoke($Subject, 'AblePolecat_Registry_Class');
        $Registry = $CommandResult->value();
        if (isset($Registry)) {
          $CommandTargets = $Registry->getClassListByKey(AblePolecat_Registry_Class::KEY_INTERFACE, 'AblePolecat_Command_TargetInterface');
          foreach ($CommandTargets as $className => $classInfo) {
            self::$Mode->CommandChain[$className] = $Registry->loadClass($className);
          }
        }
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
    foreach($this->CommandChain as $targetClassName => $Target) {
      //
      // @todo: handle accumulated command execution results
      //
      $SubResult = $Target->execute($Command);
    }
    
    if (!isset($Result)) {
      //
      // Pass command to next link in chain of responsibility
      //
      $Result = $this->delegateCommand($Command);
    }
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
      case 'forward':
        $ValidLink = is_a($Target, 'AblePolecat_Mode_User');
        break;
      case 'reverse':
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Server');
        break;
    }
    return $ValidLink;
  }
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    $this->CommandChain = array();
  }
}