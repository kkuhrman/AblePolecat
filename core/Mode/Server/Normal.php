<?php
/**
 * @file: Normal.php
 * Boots Able Polecat server in normal mode.
 */

class AblePolecat_Mode_Normal extends AblePolecat_Mode_ServerAbstract {
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    parent::initialize();
    set_error_handler(array('AblePolecat_Mode_ServerAbstract', 'defaultErrorHandler'));
    set_exception_handler(array('AblePolecat_Mode_ServerAbstract', 'defaultExceptionHandler'));
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
   * @return AblePolecat_Mode_Normal or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    $ServerMode = self::ready();
    if (!$ServerMode) {
      //
      // Create instance of server mode
      //
      $ServerMode = new AblePolecat_Mode_Normal();
      
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_Server::wakeup();
      if (isset($Environment)) {
        $ServerMode->Environment = $Environment;
      }
      else {
        throw new AblePolecat_Environment_Exception('Failed to load Able Polecat server environment.',
          ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION);
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