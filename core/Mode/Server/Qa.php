<?php
/**
 * @file: Qa.php
 * Boots Able Polecat server in test (quality assurance) mode.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Clock.php');

class AblePolecat_Mode_Qa extends AblePolecat_Mode_ServerAbstract {
  
  /**
   * @var AblePolecat_Clock Internal timer.
   */
  private $Clock;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    parent::initialize();
    set_error_handler(array('AblePolecat_Mode_ServerAbstract', 'defaultErrorHandler'));
    set_exception_handler(array('AblePolecat_Mode_ServerAbstract', 'defaultExceptionHandler'));
    $this->Clock = new AblePolecat_Clock();
    $this->Clock->start();
  }
  
  /**
   * @return AblePolecat_Clock Internal timer.
   */
  public function getClock() {
    return $this->Clock;
  }
  
  /**
   * Get ellapsed time since mode was started.
   *
   * @param bool $asString If TRUE will return time expressed as string.
   *
   * @return mixed Ellapsed time.
   */
  public function getElapsedTime($asString = TRUE) {
    return $this->Clock->getElapsedTime(AblePolecat_Clock::ELAPSED_TIME_TOTAL_ACTIVE, $asString);
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
      $ServerMode = new AblePolecat_Mode_Qa();
      
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