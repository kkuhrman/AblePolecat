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
  public static function load() {
  
    $Environment = NULL;
    try {
      $Environment = AblePolecat_EnvironmentAbstract::getCurrent();
    }
    catch (AblePolecat_Environment_Exception $e) {
      //
      // Create environment object.
      //
      // Global variable provides access to environment object during load.
      //
      $GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP'] = new AblePolecat_Environment_Default();
      $Environment = $GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP'];
      
      //
      // Initialize the default logger (CSV file).
      //
      $Environment->registerLoadableClass(
        'AblePolecat_Log_Csv',
        ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'Csv.php',
        'wakeup'
      );
      $defaultLogger = $Environment->loadClass('AblePolecat_Log_Csv');
      $Environment->setDefaultLogger($defaultLogger);

      //
      // Start or resume session.
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
      // Initialize application access control.
      //
      // Create instance of AblePolecat_AccessControl_Agent_Application 
      // which must implement AblePolecat_AccessControl_AgentInterface.
      // This should have access to config file, which must implement 
      // AblePolecat_AccessControl_ResourceInterface.
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
      // Load application configuration settings.
      //    
      // Load AblePolecat_Storage_File_Conf, which must implement 
      // AblePolecat_AccessControl_ResourceInterface. Will use agent 
      // created in #4 to gain access to this. If file does not exist 
      // initialization routine will create it with default settings.
      //
      $Environment->registerLoadableClass(
        'AblePolecat_Conf_Default',
        ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Default.php',
        'touch'
      );
      $Config = $Environment->loadClass('AblePolecat_Conf_Default');
      if (isset($Config)) {
        //
        // Grant open permission on config file to agent.
        //
        $Config->setPermission($Agent, AblePolecat_AccessControl_Constraint_Open::getId());
        $Config->setPermission($Agent, AblePolecat_AccessControl_Constraint_Read::getId());
        
        //
        // Set configuration file/path.
        //
        $conf_path = NULL;
        $filename = 'default.xml';
        $conf_path = AblePolecat_Conf_Server::getDefaultSubDir() . DIRECTORY_SEPARATOR . $filename;
        $ConfigUrl = AblePolecat_AccessControl_Resource_Locater::create($conf_path, ABLE_POLECAT_CONF_PATH);
        $Environment->setConf($Config, $ConfigUrl);
      }
      else {
        throw new AblePolecat_Environment_Exception("Failure to access/set application configuration.", 
          AblePolecat_Environment_Exception::ERROR_ENV_BOOTSTRAP_CONFIG);
      }

      //
      // Register and load modules.
      //
      $Environment->registerLoadableClass(
        'AblePolecat_Conf_Module',
        ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Module.php',
        'touch'
      );
      $Environment->registerModules();
      $Environment->loadModules();

      //
      // @todo: Start application service bus.
      //

      //
      // Clean up and return Environment ready to go.
      //
      unset($GLOBALS['ABLE_POLECAT_ENVIRONMENT_BOOTSTRAP']);
      $GLOBALS['ABLE_POLECAT_ENVIRONMENT'] = $Environment;
    }
    return self::getCurrent();
  }
}
