<?php
/**
 * @file: User.php
 * Base class for User mode (password, security token protected etc).
 */
 
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Mode.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'User.php')));

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
        $ValidLink = is_a($Target, 'AblePolecat_ModeInterface');
        break;
      case 'reverse':
        $ValidLink = is_a($Target, 'AblePolecat_Mode_Application');
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
    // Check for required server resources.
    // (will throw exception if not ready).
    //
    AblePolecat_Server::getApplicationMode();    
  }
  
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
      // @todo get user settings from session ($Subject).
      //
        
      //
      // @todo: load environment settings in initialize()
      //
      // $Environment = AblePolecat_Environment_User::wakeup($Subject);
      // if (isset($Environment)) {
        // self::$Mode->setEnvironment($Environment);
      // }
      // else {
        // throw new AblePolecat_Environment_Exception('Failed to load Able Polecat user environment.',
          // AblePolecat_Error::BOOT_SEQ_VIOLATION);
      // }
    }
      
    return self::$Mode;
  }
}