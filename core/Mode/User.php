<?php
/**
 * @file: User.php
 * Base class for User mode (password, security token protected etc).
 */
 
require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Mode.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Environment', 'User.php')));

class AblePolecat_Mode_User extends AblePolecat_ModeAbstract {
  
  /**
   * @var Instance of Singleton.
   */
  private static $UserMode;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    //
    // Start in 'not ready' state
    //
    self::$UserMode = NULL;
    
    //
    // Initialize parents
    //
    parent::initialize();
    
    //
    // Check for required server resources.
    // (will throw exception if not ready).
    //
    AblePolecat_Server::getApplicationMode();    
  }
  
  /**
   * Similar to DOM ready() but for Able Polecat application mode.
   *
   * @return AblePolecat_Mode_ApplicationAbstract or FALSE.
   */
  public static function ready() {
    $ready = FALSE;
    if (isset(self::$UserMode)) {
      $ready = self::$UserMode;
    }
    return $ready;
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
    self::$UserMode = NULL;
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$UserMode)) {
      //
      // Create instance of user mode
      //
      $UserMode = new AblePolecat_Mode_User();
      
      //
      // @todo get user settings from session ($Subject).
      //
        
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_User::wakeup($Subject);
      if (isset($Environment)) {
        $UserMode->Environment = $Environment;
      }
      else {
        throw new AblePolecat_Environment_Exception('Failed to load Able Polecat user environment.',
          AblePolecat_Error::BOOT_SEQ_VIOLATION);
      }
      
      self::$UserMode = $UserMode;
    }
      
    return self::$UserMode;
  }
}