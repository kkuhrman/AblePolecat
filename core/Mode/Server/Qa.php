<?php
/**
 * @file: Qa.php
 * Boots Able Polecat server in test (quality assurance) mode.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Clock.php');

class AblePolecat_Mode_Qa extends AblePolecat_Mode_ServerAbstract {
  
  /**
   * @var Used for performance monitoring.
   */
  private static $Clock;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    parent::initialize();
    set_error_handler(array('AblePolecat_Mode_ServerAbstract', 'defaultErrorHandler'));
    set_exception_handler(array('AblePolecat_Mode_ServerAbstract', 'defaultExceptionHandler'));
    self::$Clock = new AblePolecat_Clock();
    self::$Clock->start();
  }
  
  /**
   * Get ellapsed time since mode was started.
   *
   * @param bool $asString If TRUE will return time expressed as string.
   *
   * @return mixed Ellapsed time.
   */
  public static function getElapsedTime($asString = TRUE) {
    $ellapsed_time = NULL;
    if (isset(self::$Clock)) {
      $ellapsed_time = self::$Clock->getElapsedTime(AblePolecat_Clock::ELAPSED_TIME_TOTAL_ACTIVE, $asString);
    }
    return $ellapsed_time;
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
      $ServerMode = new AblePolecat_Mode_Dev();
      
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
    return $ServerMode;
  }
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep() {
    //
    // Persist...
    //
    self::$Singleton = NULL;
  }
}