<?php
/**
 * @file: Default.php
 * Default environment for Able Polecat (text-based web application).
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Environment.php');

class AblePolecat_Environment_Default extends AblePolecat_EnvironmentAbstract {
  
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    parent::initialize();
  }
  
  /**
   * Initialize the environment for Able Polecat.
   *
   * @return AblePolecat_Environment_Default.
   */
  public static function bootstrap() {
  
    $Environment = NULL;
    try {
      $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
    }
    catch (AblePolecat_Environment_Exception $e) {
      //
      // 1. Create environment object.
      //
      // Global variable provides access to environment object during bootstrap.
      //
      $GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP'] = new AblePolecat_Environment_Default();
      $Environment = $GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP'];

      //
      // 2. Start or resume session.
      //
      $Environment->registerLoadableClass(
        'AblePolecat_Session',
        ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Session.php',
        'start'
      );
      $Session = $Environment->loadClass('AblePolecat_Session');
      if (!isset($Session)) {
        $Environment->handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOTSTRAP_SESSION);
      }

      //
      // 3. Initialize application access control.
      //
      //    Create instance of AblePolecat_AccessControl_Agent_Application 
      //    which must implement AblePolecat_AccessControl_AgentInterface.
      //    This should have access to config file, which must implement 
      //    AblePolecat_AccessControl_ResourceInterface.
      //
      $Environment->registerLoadableClass(
        'AblePolecat_AccessControl_Agent_Application', 
        ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl' . DIRECTORY_SEPARATOR . 'Agent' . DIRECTORY_SEPARATOR . 'Application.php',
        'load'
      );
      $Agent = $Environment->loadClass('AblePolecat_AccessControl_Agent_Application');
      if (isset($Agent)) {
        $Environment->addAgent($Agent);
      }
      else {
        $Environment->handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOTSTRAP_AGENT);
      }

      //
      // 4. @todo: Load application configuration settings.
      //    
      //    Load AblePolecat_Storage_File_Conf, which must implement 
      //    AblePolecat_AccessControl_ResourceInterface. Will use agent 
      //    created in #4 to gain access to this. If file does not exist 
      //    initialization routine will create it with default settings.
      //
      // $Environment->registerLoadableClass(
        // 'AblePolecat_Conf_Application',
        // ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Application.php',
        // 'touch'
      // );

      //
      // 5. @todo: Register and load modules.
      //

      //
      // 6. @todo: Register and load loggers.
      //

      //
      // 7. @todo: Start application service bus.
      //

      //
      // 8. Clean up and return Environment ready to go.
      //
      unset($GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP']);
      $GLOBALS['ABLE_POLECAT_ENVIRONMENT'] = $Environment;
    }
    return self::getCurrent();
  }
}
