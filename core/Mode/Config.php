<?php
/**
 * @file      polecat/core/Mode/Config.php
 * @brief     Configuration mode checks critical settings, attempts to fix problems.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
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
   * @var AblePolecat_Database_Pdo
   */
  private $CoreDatabase;
  
  /**
   * @var Array Core server database connection settings.
   */
  private $CoreDatabaseConnectionSettings;
  
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
      if (FALSE === self::verifySystemFile(AblePolecat_Log_Boot::LOG_NAME_BOOTSEQ)) {
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
      
      if (self::getLocalProjectConfFile()) {
        //
        // Project configuration file exists, attempt to connect to database.
        //
        self::$ConfigMode->getCoreDatabase();
        
        if (isset(self::$ConfigMode->CoreDatabase) && self::$ConfigMode->CoreDatabase->ready()) {
          //
          // Project database is initialized and ready.
          //
          self::reportBootState(self::BOOT_STATE_CONFIG, 'Host configuration initialized.');
        }
        else {
          //
          // Project database is not ready. Trigger error if not install mode.
          // Peek at HTTP request.
          //
          $installMode = FALSE;
          isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
          switch ($method) {
            default:
              break;
            case 'GET':
              //
              // Verify that the local project configuration file is writeable.
              //
              $localProjectConfFilePath = self::getLocalProjectConfFilePath();
              $installMode = is_writeable($localProjectConfFilePath);
              break;
            case 'POST':
              if (isset($_POST[AblePolecat_Transaction_RestrictedInterface::ARG_REFERER])) {
                $referer = $_POST[AblePolecat_Transaction_RestrictedInterface::ARG_REFERER];
                if ($referer === AblePolecat_Resource_Restricted_Install::UUID) {
                  $installMode = TRUE;
                }
              }
              break;
          }
          if ($installMode === FALSE) {
            AblePolecat_Command_Chain::triggerError('Boot sequence violation: Project database is not ready.');
          }
        }
      }
      else {
        AblePolecat_Command_Chain::triggerError('Boot sequence violation: Project configuration file is not accessible.');
      }
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
   * Database functions.
   ********************************************************************************/
  
  /**
   * @return boolean TRUE if core database connection is established, otherwise FALSE.
   */
  public static function coreDatabaseIsReady() {
    
    $dbReady = FALSE;
    if (isset(self::$ConfigMode) && isset(self::$ConfigMode->CoreDatabase)) {
      $dbReady = self::$ConfigMode->CoreDatabase->ready();
    }
    return $dbReady;
  }
  /**
   * Initialize connection to core database.
   *
   * More than one application database can be defined in server conf file. 
   * However, only ONE application database can be active per server mode. 
   * If 'mode' attribute is empty, polecat will assume any mode. Otherwise, 
   * database is defined for given mode only. The 'use' attribute indicates 
   * that the database should be loaded for the respective server mode. Polecat 
   * will scan database definitions until it finds one suitable for the current 
   * server mode where the 'use' attribute is set. 
   * @code
   * <database id="core" name="polecat" mode="server" use="1">
   *  <dsn>mysql://username:password@localhost/databasename</dsn>
   * </database>
   * @endcode
   *
   * Only one instance of core (server mode) database can be active.
   * Otherwise, Able Polecat stops boot and throws exception.
   *
   */
  public function getCoreDatabase() {
    
    if (!isset($this->CoreDatabase)) {
      //
      // Core database connection settings.
      //
      $this->CoreDatabaseConnectionSettings = array();
      $this->CoreDatabaseConnectionSettings['connected'] = FALSE;
      
      //
      // Get DSN from local project configuration file.
      //
      $localProjectConfFile = self::getLocalProjectConfFile();
      $coreDatabaseElementId = self::getCoreDatabaseId();
      $Node = AblePolecat_Dom::getElementById($localProjectConfFile, $coreDatabaseElementId);
      if (isset($Node)) {
        $this->CoreDatabaseConnectionSettings['name'] = $Node->getAttribute('name');
        foreach($Node->childNodes as $key => $childNode) {
          if($childNode->nodeName == 'polecat:dsn') {
            $this->CoreDatabaseConnectionSettings['dsn'] = $childNode->nodeValue;
            break;
          }
        }
      }
      else {
        throw new AblePolecat_Mode_Exception("Local project configuration file does not contain a locater for $coreDatabaseElementId.");
      }
      
      if (isset($this->CoreDatabaseConnectionSettings['dsn'])) {
        //
        // Attempt a connection.
        //
        $this->CoreDatabase = AblePolecat_Database_Pdo::wakeup($this->getAgent());
        $DbUrl = AblePolecat_AccessControl_Resource_Locater_Dsn::create($this->CoreDatabaseConnectionSettings['dsn']);
        $this->CoreDatabaseConnectionSettings['connected'] = $this->CoreDatabase->open($this->getAgent(), $DbUrl);
        $dbErrors = self::$ConfigMode->CoreDatabase->flushErrors();
        foreach($dbErrors as $errorNumber => $error) {
          $error = AblePolecat_Database_Pdo::getErrorMessage($error);
          AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, $error);
        }
      }
    }
    return $this->CoreDatabase;
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
      AblePolecat_Log_Boot::LOG_NAME_BOOTSEQ
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
   * @var Array Core server database connection settings.
   */
  public static function getCoreDatabaseConnectionSettings() {
    $CoreDatabaseConnectionSettings = NULL;
    if (isset(self::$ConfigMode)) {
      $CoreDatabaseConnectionSettings = self::$ConfigMode->CoreDatabaseConnectionSettings;
    }
    return $CoreDatabaseConnectionSettings;
  }
  
  /**
   * @return DOMDOcument The local project configuration file.
   */
  public static function getCoreClassLibraryConfFile() {
    
    $coreClassLibraryConfFile = NULL;
    if (isset(self::$ConfigMode)) {
      if (!isset(self::$ConfigMode->coreClassLibraryConfFile)) {
        $Agent = AblePolecat_AccessControl_Agent_System::wakeup();
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
      case AblePolecat_Log_Boot::LOG_NAME_BOOTSEQ:
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
              case AblePolecat_Log_Boot::LOG_NAME_BOOTSEQ:
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
    $this->CoreDatabase = NULL;
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