<?php
/**
 * @file      polecat/core/Mode/User.php
 * @brief     Base class for User mode (password, security token protected etc).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'User.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'User.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Mode.php');

class AblePolecat_Mode_User extends AblePolecat_ModeAbstract {
  
  /**
   * Constants.
   */
  const UUID = 'e7f5dd90-5f4c-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat User Mode';
  
  /**
   * @var Instance of Singleton.
   */
  private static $Mode;
  
  /**
   * @var AblePolecat_AccessControl_AgentInterface
   */
  private $Agent;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $Environment;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
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
   * @return AblePolecat_Mode_User or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Mode)) {
      try {
        //
        // Create instance of user mode
        //
        self::$Mode = new AblePolecat_Mode_User($Subject);
        
        //
        // Set chain of responsibility relationship
        //
        $Subject->setForwardCommandLink(self::$Mode);
        self::$Mode->setReverseCommandLink($Subject);
        
        //
        // Access control agent (super user).
        //
        self::$Mode->Agent = AblePolecat_AccessControl_Agent_User::wakeup(self::$Mode);
        
        //
        // Load environment/configuration
        //
        //
        self::$Mode->Environment = AblePolecat_Environment_User::wakeup(self::$Mode->Agent);
      }
      catch(Exception $Exception) {
        self::$Mode = NULL;
        throw new AblePolecat_Server_Exception(
          'Failed to initialize user mode. ' . $Exception->getMessage(),
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
      case '54d2e7d0-77b9-11e3-981f-0800200c9a66':
        $Result = new AblePolecat_Command_Result($this->Agent, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
        break;
      case '85fc7590-724d-11e3-981f-0800200c9a66':
        //
        // Log
        //
        switch($Command->getEventSeverity()) {
          default:
            break;
          case AblePolecat_LogInterface::USER_INFO:
          case AblePolecat_LogInterface::USER_STATUS:
          case AblePolecat_LogInterface::USER_WARNING:
            //
            // Do not pass these to next link in CoR.
            //
            $Result = new AblePolecat_Command_Result(NULL, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
            break;
        }
        break;
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
        $ValidLink = is_a($Target, 'AblePolecat_ModeInterface');
        break;
      case 'reverse':
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Application');
        break;
    }
    return $ValidLink;
  }
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
  }
}