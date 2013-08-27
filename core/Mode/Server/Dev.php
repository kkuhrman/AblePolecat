<?php
/**
 * @file: Dev.php
 * Boots Able Polecat server in development mode.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Server', 'Check', 'Paths.php')));

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