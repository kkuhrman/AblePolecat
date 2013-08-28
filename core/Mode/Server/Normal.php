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
   * Initialize and return object implementing AblePolecat_Mode.
   */
  public static function wakeup() {
    
    $ServerMode = self::ready();
    if (!$ServerMode) {
      //
      // Create instance of server mode
      //
      $ServerMode = new AblePolecat_Mode_Normal();
      
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_Server::load();
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
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep() {
    //
    // Persist...
    //
    self::$ServerMode = NULL;
  }
}