<?php
/**
 * @file: Application.php
 * Base class for Application modes (most protected).
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Mode.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Environment', 'Application.php')));

class AblePolecat_Mode_Application extends AblePolecat_ModeAbstract {
  
  /**
   * @var AblePolecat_Mode_ApplicationAbstract Concrete ApplicationMode instance.
   */
  protected static $ApplicationMode;
  
  /**
   * @var bool Prevents some code from exceuting prior to start().
   */
  protected static $ready = FALSE;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    self::$ApplicationMode = NULL;
    
    //
    // Check for required server resources.
    // (these will throw exception if not ready).
    //
    AblePolecat_Server::getBootMode();
    AblePolecat_Server::getClassRegistry();
    AblePolecat_Server::getDefaultLog();
    AblePolecat_Server::getServerMode();
    // AblePolecat_Server::getServiceBus();
  }
  
  /**
   * Initialize and return object implementing AblePolecat_Mode.
   */
  public static function wakeup() {
    
    $ApplicationMode = self::ready();
    if (!$ApplicationMode) {
      //
      // Create instance of server mode
      //
      $ApplicationMode = new AblePolecat_Mode_Application();
      
      //
      // Load environment settings
      //
      $Environment = AblePolecat_Environment_Application::load();
      if (isset($Environment)) {
        $ApplicationMode->Environment = $Environment;
      }
      else {
        throw new AblePolecat_Environment_Exception('Failed to load Able Polecat application environment.',
          ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION);
      }
      
      //
      // wakeup() completed successfully
      //
      self::$ApplicationMode = $ApplicationMode;
      self::$ready = TRUE;
    }
    return self::$ApplicationMode;
  }
  
  /**
   * Similar to DOM ready() but for Able Polecat application mode.
   *
   * @return AblePolecat_Mode_ApplicationAbstract or FALSE.
   */
  public static function ready() {
    $ready = self::$ready;
    if ($ready) {
      $ready = self::$ApplicationMode;
    }
    return $ready;
  }
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep() {
  }
}