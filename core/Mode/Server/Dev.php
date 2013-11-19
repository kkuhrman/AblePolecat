<?php
/**
 * @file: Dev.php
 * Boots Able Polecat server in development mode.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Server', 'Check', 'Paths.php')));

class AblePolecat_Mode_Dev extends AblePolecat_Mode_ServerAbstract {
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // report any kind of error
    //
    error_reporting(E_ALL);
    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);
    
    //
    // Check system paths.
    //
    if(!AblePolecat_Server_Check_Paths::go()) {
      throw new AblePolecat_Server_Exception(AblePolecat_Server_Check_Paths::getErrorMessage(), 
        AblePolecat_Server_Check_Paths::getErrorCode());
    }
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // todo: Persist...
    //
    self::$ServerMode = NULL;
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Dev or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    $ServerMode = self::ready();
    if (!$ServerMode) {
      //
      // Create instance of server mode
      //
      $ServerMode = new AblePolecat_Mode_Dev();
      
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_Server::wakeup();
      if (isset($Environment)) {
        $ServerMode->Environment = $Environment;
      }
      else {
        throw new AblePolecat_Environment_Exception('Failed to load Able Polecat server environment.',
          AblePolecat_Error::BOOT_SEQ_VIOLATION);
      }
      
      //
      // wakeup() completed successfully
      //
      self::$ServerMode = $ServerMode;
      self::$ready = TRUE;
    }
    return self::$ServerMode;
  }
}