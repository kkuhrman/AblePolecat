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
   * @return AblePolecat_Mode_Qa or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    $ServerMode = self::ready();
    if (!$ServerMode) {
      //
      // Create instance of server mode
      //
      $ServerMode = new AblePolecat_Mode_Qa();
      
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