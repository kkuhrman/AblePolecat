<?php
/**
 * @file      polecat/core/Mode/Config.php
 * @brief     Configuration mode checks critical settings, attempts to fix problems.
 *
 * Config mode has the following duties:
 * 1. Verify local environment path settings.
 * 2. Establish system user connection to project database.
 * 3. Load class library and class registries.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Connector.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode.php')));

class AblePolecat_Mode_Config extends AblePolecat_ModeAbstract {
  
  const UUID = '3599ce6f-ad72-11e4-976e-0050569e00a2';
  const NAME = 'AblePolecat_Mode_Config';
  
  const VAR_CONF_PATH_DBSCHEMA  = 'conf_path_dbschema';
  const VAR_CONF_PATH_CORE      = 'conf_path_core';
  
  /**
   * @var AblePolecat_Mode_Config Instance of singleton.
   */
  private static $ConfigMode;
  
  /**
   * @var string Full path to boot log file.
   */
  private $bootLogFilePath;
  
  /**
   * @var DOMDOcument The core class library configuration file.
   */
  private $coreClassLibraryConfFile;
  
  /**
   * @var string Full path to core class library configuration file.
   */
  private $coreClassLibraryConfFilepath;
  
  /**
   * @var DOMDOcument The local project configuration file.
   */
  private $localProjectConfFile;
  
  /**
   * @var string Full path to local project configuration file.
   */
  private $localProjectConfFilepath;
  
  /**
   * @var DOMDOcument The master project configuration file.
   */
  private $masterProjectConfFile;
  
  /**
   * @var string Full path to master project configuration file.
   */
  private $masterProjectConfFilepath;
  
  /**
   * @var DOMDOcument Module project configuration files.
   */
  private $moduleConfFiles;
  
  /**
   * @var string Full paths to module project configuration files.
   */
  private $moduleConfFilepaths;
  
  /**
   * @var Array.
   */
  private $Variables;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
   
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    try {
      parent::sleep();
    }
    catch (AblePolecat_Exception $Exception) {
    }
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$ConfigMode)) {      
      //
      // Create instance of singleton.
      //
      self::$ConfigMode = new AblePolecat_Mode_Config();
      
      //
      // Verify ./etc/polecat/conf
      //
      $configFileDirectory = AblePolecat_Server_Paths::getFullPath('conf');
      if (FALSE === AblePolecat_Server_Paths::verifyDirectory($configFileDirectory)) {
        AblePolecat_Command_Chain::triggerError('Boot sequence violation: Project configuration directory is not accessible.');
      }
      
      //
      // Initialize boot log.
      //
      if (FALSE === self::verifySystemFile(AblePolecat_Log_Boot::LOG_NAME_ERROR)) {
        AblePolecat_Command_Chain::triggerError('Boot sequence violation: Boot log file is not accessible.');
      }
      
      //
      // core conf file paths.
      //
      $schemaFileName = sprintf("polecat-database-%s.xml", AblePolecat_Version::getDatabaseSchemaNumber());
      $schemaFilePath = implode(DIRECTORY_SEPARATOR, array(dirname(ABLE_POLECAT_CORE), 'etc', 'polecat', 'database', $schemaFileName));
      self::$ConfigMode->Variables[self::VAR_CONF_PATH_DBSCHEMA] = $schemaFilePath;
      
      //
      // Core class library path.
      //
      self::$ConfigMode->Variables[self::VAR_CONF_PATH_CORE] = self::getCoreClassLibraryConfFilepath();
      
      if (!self::getLocalProjectConfFile()) {
        AblePolecat_Command_Chain::triggerError('Boot sequence violation: Project configuration file is not accessible.');
      }
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Config mode initialized.');
    }
    return self::$ConfigMode;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Command_TargetInterface.
   ********************************************************************************/
  
  /**
   * Execute a command or pass back/forward chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public function execute(AblePolecat_CommandInterface $Command) {
    
    $Result = NULL;
    
    //
    // @todo: check invoker access rights
    //
    switch ($Command::getId()) {
      default:
        //
        // End of CoR. FAIL.
        //
        $Result = new AblePolecat_Command_Result();
        break;
    }
    
    return $Result;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_ModeInterface.
   ********************************************************************************/
  
  /**
   * Returns assigned value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   *
   * @return mixed Assigned value of given variable or NULL.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function getEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name) {
    
    $VariableValue = NULL;
    if (isset(self::$ConfigMode) && isset(self::$ConfigMode->Variables[$name])) {
      $VariableValue = self::$ConfigMode->Variables[$name];
    }
    else {
      throw new AblePolecat_Mode_Exception("Cannot access variable '$name'. Environment is not initialized.");
    }
    return $VariableValue;
  }
  
  /**
   * Assign value of given environment variable.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent seeking access.
   * @param string $name Name of requested environment variable.
   * @param mixed $value Value of variable.
   *
   * @return bool TRUE if variable is set, otherwise FALSE.
   * @throw AblePolecat_Mode_Exception If environment is not initialized.
   */
  public static function setEnvironmentVariable(AblePolecat_AccessControl_AgentInterface $Agent, $name, $value) {
    $VariableSet = NULL;
    if (isset(self::$ConfigMode) && isset(self::$ConfigMode->Variables)) {
      $VariableSet = $value;
      self::$ConfigMode->Variables[$name]= $value;
    }
    else {
      throw new AblePolecat_Mode_Exception("Cannot access variable '$name'. Environment is not initialized.");
    }
    return $VariableSet;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Returns Full path to boot log file on local machine.
   *
   * @param bool $asStr If FALSE, return path hierarchy as array, otherwise path as string.
   *
   * @return mixed.
   */
  public static function getBootLogFilePath($asStr = TRUE) {
    
    static $bootLogFilePathParts;
    $bootLogFilePathParts = array(
      AblePolecat_Server_Paths::getFullPath('var'), 
      'log',
      AblePolecat_Log_Boot::LOG_NAME_ERROR
    );
    $bootLogFilePath = NULL;
    
    if ($asStr) {
        if (isset(self::$ConfigMode)) {
          if (!isset(self::$ConfigMode->bootLogFilePath)) {
            self::$ConfigMode->bootLogFilePath = implode(DIRECTORY_SEPARATOR, $bootLogFilePathParts);
          }
          $bootLogFilePath = self::$ConfigMode->bootLogFilePath;
        }
    }
    else {
      $bootLogFilePath = $bootLogFilePathParts;
    }
    
    return $bootLogFilePath;
  }
  
  /**
   * @return DOMDOcument The local project configuration file.
   */
  public static function getCoreClassLibraryConfFile() {
    
    $coreClassLibraryConfFile = NULL;
    if (isset(self::$ConfigMode)) {
      if (!isset(self::$ConfigMode->coreClassLibraryConfFile)) {
        $Agent = AblePolecat_AccessControl_Agent_User_System::wakeup();
        $coreFilePath = self::getCoreClassLibraryConfFilepath();
        self::$ConfigMode->coreClassLibraryConfFile = new DOMDocument();
        self::$ConfigMode->coreClassLibraryConfFile->load($coreFilePath);
      }
      $coreClassLibraryConfFile = self::$ConfigMode->coreClassLibraryConfFile;
    }
    return $coreClassLibraryConfFile;
  }
  
  /**
   * Returns Full path to local project configuration file.
   *
   * @param bool $asStr If FALSE, return path hierarchy as array, otherwise path as string.
   *
   * @return mixed.
   */
  public static function getCoreClassLibraryConfFilepath($asStr = TRUE) {
    
    static $coreClassLibraryConfFilepathParts;
    $coreFileName = sprintf("polecat-core-%s.xml", AblePolecat_Version::getCoreClassLibraryNumber());
    $coreClassLibraryConfFilepathParts = array(
      dirname(ABLE_POLECAT_CORE), 
      'etc',
      'polecat',
      'core',
      $coreFileName
    );
    $coreClassLibraryConfFilepath = NULL;
    
    if ($asStr) {
        if (isset(self::$ConfigMode)) {
          if (!isset(self::$ConfigMode->coreClassLibraryConfFilepath)) {
            self::$ConfigMode->coreClassLibraryConfFilepath = implode(DIRECTORY_SEPARATOR, $coreClassLibraryConfFilepathParts);
          }
          $coreClassLibraryConfFilepath = self::$ConfigMode->coreClassLibraryConfFilepath;
        }
    }
    else {
      $coreClassLibraryConfFilepath = $coreClassLibraryConfFilepathParts;
    }
    
    return $coreClassLibraryConfFilepath;
  }
  
  /**
   * @return string.
   */
  public static function getCoreDatabaseId() {
    static $coreDatabaseElementId;
    !isset($coreDatabaseElementId) ? $coreDatabaseElementId = sprintf("polecat-database-%s", AblePolecat_Version::getDatabaseSchemaNumber()) : NULL;
    return $coreDatabaseElementId;
  }
  
  /**
   * @return DOMDOcument The local project configuration file.
   */
  public static function getLocalProjectConfFile() {
    
    $localProjectConfFile = NULL;
    
    if (isset(self::$ConfigMode)) {
      if (isset(self::$ConfigMode->localProjectConfFile)) {
        $localProjectConfFile = self::$ConfigMode->localProjectConfFile;
      }
      else {
        if (self::verifySystemFile(AblePolecat_Server_Paths::CONF_FILENAME_PROJECT)) {
          $localProjectConfFilepath = self::getLocalProjectConfFilepath();
          self::$ConfigMode->localProjectConfFile = new DOMDocument();
          self::$ConfigMode->localProjectConfFile->load($localProjectConfFilepath);
          $localProjectConfFile = self::$ConfigMode->localProjectConfFile;
        }
      }
    }
    return $localProjectConfFile;
  }
  
  /**
   * Returns Full path to local project configuration file.
   *
   * @param bool $asStr If FALSE, return path hierarchy as array, otherwise path as string.
   *
   * @return mixed.
   */
  public static function getLocalProjectConfFilepath($asStr = TRUE) {
    
    static $localProjectConfFilepathParts;
    $localProjectConfFilepathParts = array(
      AblePolecat_Server_Paths::getFullPath('usr'), 
      'etc',
      'polecat',
      'conf',
      AblePolecat_Server_Paths::CONF_FILENAME_PROJECT
    );
    $localProjectConfFilepath = NULL;
    
    if ($asStr) {
        if (isset(self::$ConfigMode)) {
          if (!isset(self::$ConfigMode->localProjectConfFilepath)) {
            self::$ConfigMode->localProjectConfFilepath = implode(DIRECTORY_SEPARATOR, $localProjectConfFilepathParts);
          }
          $localProjectConfFilepath = self::$ConfigMode->localProjectConfFilepath;
        }
    }
    else {
      $localProjectConfFilepath = $localProjectConfFilepathParts;
    }
    
    return $localProjectConfFilepath;
  }
  
  /**
   * @return DOMDOcument The master project configuration file.
   */
  public static function getMasterProjectConfFile() {
    
    $masterProjectConfFile = NULL;
    
    if (isset(self::$ConfigMode)) {
      if (!isset(self::$ConfigMode->masterProjectConfFile)) {
        $masterProjectConfFilepath = self::getMasterProjectConfFilepath();
        self::$ConfigMode->masterProjectConfFile = new DOMDocument();
        self::$ConfigMode->masterProjectConfFile->load($masterProjectConfFilepath);
      }
      $masterProjectConfFile = self::$ConfigMode->masterProjectConfFile;
    }
    return $masterProjectConfFile;
  }
  
  /**
   * Returns Full path to master project configuration file.
   *
   * @param bool $asStr If FALSE, return path hierarchy as array, otherwise path as string.
   *
   * @return mixed.
   */
  public static function getMasterProjectConfFilepath($asStr = TRUE) {
    
    static $masterProjectConfFilepathParts;
    $masterProjectConfFilepathParts = array(
      AblePolecat_Server_Paths::getFullPath('conf'), 
      AblePolecat_Server_Paths::CONF_FILENAME_PROJECT
    );
    $masterProjectConfFilepath = NULL;
    
    if ($asStr) {
        if (isset(self::$ConfigMode)) {
          if (!isset(self::$ConfigMode->masterProjectConfFilepath)) {
            self::$ConfigMode->masterProjectConfFilepath = implode(DIRECTORY_SEPARATOR, $masterProjectConfFilepathParts);
          }
          $masterProjectConfFilepath = self::$ConfigMode->masterProjectConfFilepath;
        }
    }
    else {
      $masterProjectConfFilepath = $masterProjectConfFilepathParts;
    }
    
    return $masterProjectConfFilepath;
  }
  
  /**
   * Return local or master project configuration file for given module.
   *
   * Preference is given to local project configuration file, allowing developers
   * to store libraries and modules in non-standard directories.
   *
   * @param AblePolecat_Registry_Entry_ClassLibrary $ClassLibraryRegistration
   *
   * @return DOMDocument or NULL.
   */
  public static function getModuleConfFile(AblePolecat_Registry_Entry_ClassLibrary $ClassLibraryRegistration) {
    
    $moduleConfFile = NULL;
    
    if ($ClassLibraryRegistration->libType === 'mod') {
      if (isset(self::$ConfigMode)) {
        $id = $ClassLibraryRegistration->id;
        if (!isset(self::$ConfigMode->moduleConfFiles)) {
          self::$ConfigMode->moduleConfFiles = array();
        }
        if (!isset(self::$ConfigMode->moduleConfFiles[$id])) {
          $moduleConfFilepath = self::getModuleConfFilePath($ClassLibraryRegistration);
          $moduleConfFile = new DOMDocument();
          $moduleConfFile->load($moduleConfFilepath);
          self::$ConfigMode->moduleConfFiles[$id] = $moduleConfFile;
        }
        else {
          $moduleConfFile = self::$ConfigMode->moduleConfFiles[$id];
        }
      }
    }
    return $moduleConfFile;
  }
  
  /**
   * Return full path of master project configuration file for given module.
   *
   * Able Polecat allows users to override settings in the master project 
   * configuration file(s) by creating a local copy in ./usr/etc. Polecat 
   * will use any local configuration file if it exists; otherwise it will 
   * use the corresponding master project configuration file.
   *
   * @param AblePolecat_Registry_Entry_ClassLibrary $ClassLibraryRegistration
   * @param bool $asStr If FALSE, return path hierarchy as array, otherwise path as string.
   *
   * @return mixed.
   */
  public static function getModuleConfFilePath(AblePolecat_Registry_Entry_ClassLibrary $ClassLibraryRegistration, $asStr = TRUE) {
    
    static $moduleConfFilepathParts;
    $moduleConfFilepath = NULL;
    
    $libType = $ClassLibraryRegistration->getClassLibraryType();
    if ($libType === 'mod') {
      if (!isset($moduleConfFilepathParts)) {
        $moduleConfFilepathParts = array();
      }
      $id = $ClassLibraryRegistration->getId();
      if (!isset($moduleConfFilepathParts[$id])) {
        //
        // First check for local configuration file.
        //
        $confPath = implode(DIRECTORY_SEPARATOR, array(
          dirname($ClassLibraryRegistration->getClassLibraryFullPath()),
          'etc',
          'polecat',
          'conf',
          AblePolecat_Server_Paths::CONF_FILENAME_PROJECT
        ));
        if (!AblePolecat_Server_Paths::verifyFile($confPath)) {
          //
          // Local file not found, use master.
          //
          $confPath = implode(DIRECTORY_SEPARATOR, array(
            dirname(dirname($ClassLibraryRegistration->getClassLibraryFullPath())),
            'etc',
            'polecat',
            'conf',
            AblePolecat_Server_Paths::CONF_FILENAME_PROJECT
          ));
          if (!AblePolecat_Server_Paths::verifyFile($confPath)) {
            AblePolecat_Command_Chain::triggerError(sprintf("Cannot access local or master project configuration file for %s (%s)",
              $ClassLibraryRegistration->getName(),
              $ClassLibraryRegistration->getId()
            ));
          }
        }        
        
        $moduleConfFilepathParts[$id] = explode(DIRECTORY_SEPARATOR, $confPath);
      }
      if ($asStr) {
        if (isset(self::$ConfigMode)) {
          if (!isset(self::$ConfigMode->moduleConfFilepaths)) {
            self::$ConfigMode->moduleConfFilepaths = array();
          }
          if (!isset(self::$ConfigMode->moduleConfFilepaths[$id])) {
            self::$ConfigMode->moduleConfFilepaths[$id] = implode(DIRECTORY_SEPARATOR, $moduleConfFilepathParts[$id]);
          }
          $moduleConfFilepath = self::$ConfigMode->moduleConfFilepaths[$id];
        }
      }
      else {
        $moduleConfFilepath = $moduleConfFilepathParts[$id];
      }
    }    
    return $moduleConfFilepath;
  }
  
  /**
   * Attempts to create a local project configuration file by copying master.
   *
   * @return boolean TRUE if file was created, otherwise FALSE.
   */
  protected static function createProjectConfFile() {
    
    $localProjectConfFile = FALSE;
    
    if (isset(self::$ConfigMode)) {
      if (isset(self::$ConfigMode->localProjectConfFile)) {
        $localProjectConfFile = TRUE;
      }
      else {
        $masterProjectConfFilepath = self::getMasterProjectConfFilepath();
        $localProjectConfFilepath = self::getLocalProjectConfFilepath();
        $localProjectConfFile = copy($masterProjectConfFilepath, $localProjectConfFilepath);
      }
    }
    return $localProjectConfFile;
  }
  
  /**
   * Verifies that given system file exists or attempts to create it.
   * 
   * @var string $fileName Name of system file to initialize.
   *
   * @return mixed Full path of given file if valid, otherwise FALSE.
   */
  protected static function verifySystemFile($fileName) {
    
    $verifiedSystemFilePath = FALSE;
    
    //
    // Set given file full path for local machine.
    //
    $sysFilePathParts = NULL;
    $sysFilePath = NULL;
    switch ($fileName) {
      default:
        break;
      case AblePolecat_Server_Paths::CONF_FILENAME_PROJECT:
        $sysFilePathParts = self::getLocalProjectConfFilepath(FALSE);
        $sysFilePath = self::getLocalProjectConfFilepath();
        break;
      case AblePolecat_Log_Boot::LOG_NAME_ERROR:
        $sysFilePathParts = self::getBootLogFilePath(FALSE);
        $sysFilePath = self::getBootLogFilePath();
        break;
    }
    if (isset($sysFilePath)) {
      if (AblePolecat_Server_Paths::verifyFile($sysFilePath)) {
        $verifiedSystemFilePath = $sysFilePath;
      }
      else {
        //
        // System file does not exist, attempt to initialize it.
        //
        $sysFilePath = '';
        $sysFilePathPartsCount = count($sysFilePathParts) - 1;
        foreach($sysFilePathParts as $key => $pathPart) {
          $isDir = ($key < $sysFilePathPartsCount);
          if ($isDir) {
            //
            // Create the system file path hierarchy.
            //
            $sysFilePath .= $pathPart;
            AblePolecat_Server_Paths::touch($sysFilePath, $isDir);
            $sysFilePath .= DIRECTORY_SEPARATOR;
          }
          else {
            $sysFilePath .= $pathPart;
            switch ($fileName) {
              default:
                break;
              case AblePolecat_Server_Paths::CONF_FILENAME_PROJECT:
                if (self::createProjectConfFile()) {
                  $verifiedSystemFilePath = $sysFilePath;
                }
                break;
              case AblePolecat_Log_Boot::LOG_NAME_ERROR:
                $bootLog = AblePolecat_Log_Boot::wakeup();
                $verifiedSystemFilePath = $sysFilePath;
                break;
            }            
          }
        }
      } 
    }
    return $verifiedSystemFilePath;
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    
    parent::initialize();
    
    $this->Variables = array();
    $this->bootLogFilePath = NULL;
    $this->coreClassLibraryConfFile = NULL;
    $this->coreClassLibraryConfFilepath = NULL;
    $this->localProjectConfFile = NULL;
    $this->localProjectConfFilepath = NULL;
    $this->masterProjectConfFile = NULL;
    $this->masterProjectConfFilepath = NULL;
    $this->moduleConfFiles = array();
    $this->moduleConfFilepaths = array();
  }
}