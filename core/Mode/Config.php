<?php
/**
 * @file      polecat/core/Mode/Config.php
 * @brief     Configuration mode checks critical settings, attempts to fix problems.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Pdo.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Transaction', 'Install.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode.php')));

class AblePolecat_Mode_Config extends AblePolecat_ModeAbstract {
  
  const UUID = '3599ce6f-ad72-11e4-976e-0050569e00a2';
  const NAME = 'AblePolecat_Mode_Config';
  
  const ACTIVE_CORE_DATABASE_ID = 'active-core-db';
  
  /**
   * @var AblePolecat_Mode_Config Instance of singleton.
   */
  private static $ConfigMode;
  
  /**
   * @var string Full path to boot log file.
   */
  private $bootLogFilePath;
  
  /**
   * @var DOMDOcument The project configuration file.
   */
  private $projectConfFile;
  
  /**
   * @var string Full path to project configuration file on local machine.
   */
  private $projectConfFilepath;
  
  /**
   * @var AblePolecat_Database_Pdo
   */
  private $CoreDatabase;
  
  /**
   * @var Array Core server database connection settings.
   */
  private $CoreDatabaseConnectionSettings;
  
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
          throw new AblePolecat_Mode_Exception('Boot sequence violation: Project configuration directory is not accessible.',
            AblePolecat_Error::BOOT_SEQ_VIOLATION
          );
        }
      
      //
      // Initialize boot log.
      //
      if (FALSE === self::verifySystemFile(AblePolecat_Log_Boot::LOG_NAME_BOOTSEQ)) {
        throw new AblePolecat_Mode_Exception('Boot sequence violation: Boot log file is not accessible.',
          AblePolecat_Error::BOOT_SEQ_VIOLATION
        );
      }
      
      //
      // Peek at HTTP request.
      //
      isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
      switch ($method) {
        default:
          break;
        case 'GET':
          if (self::verifySystemFile(AblePolecat_Server_Paths::CONF_FILENAME_PROJECT)) {
            //
            // Project configuration file exists, get core database connection string. 
            //
            self::$ConfigMode->projectConfFile = new DOMDocument();
            self::$ConfigMode->projectConfFile->load(self::$ConfigMode->projectConfFilepath);
            
            //
            // Attempt to connect to database.
            //
            self::$ConfigMode->initializeCoreDatabase();
            $dbErrors = self::$ConfigMode->CoreDatabase->flushErrors();
            if (count($dbErrors)) {
              //
              // @todo: Connection was not successful (server mode will take it from here).
              //
            }
          }
          else {
            throw new AblePolecat_Mode_Exception('Boot sequence violation: Project configuration file is not accessible.',
              AblePolecat_Error::BOOT_SEQ_VIOLATION
            );
          }
          break;
        case 'POST':
          if (self::verifySystemFile(AblePolecat_Server_Paths::CONF_FILENAME_PROJECT)) {
            //
            // Project configuration file exists, get core database connection string. 
            //
            isset($_POST[AblePolecat_Transaction_Install::ARG_DB]) ? $databaseName = $_POST[AblePolecat_Transaction_Install::ARG_DB] : $databaseName = 'databasename';
            isset($_POST[AblePolecat_Transaction_Install::ARG_USER]) ? $userName = $_POST[AblePolecat_Transaction_Install::ARG_USER] : $userName = 'username';
            isset($_POST[AblePolecat_Transaction_Install::ARG_PASS]) ? $password = $_POST[AblePolecat_Transaction_Install::ARG_PASS] : $password = 'password';
            self::$ConfigMode->projectConfFile = new DOMDocument();
            self::$ConfigMode->projectConfFile->load(self::$ConfigMode->projectConfFilepath);
            $Node = AblePolecat_Dom::getElementById(self::$ConfigMode->projectConfFile, self::ACTIVE_CORE_DATABASE_ID);
            foreach($Node->childNodes as $key => $childNode) {
              if($childNode->nodeName == 'dsn') {
                $childNode->nodeValue = sprintf("mysql://%s:%s@localhost/%s", $userName, $password, $databaseName);
                break;
              }
            }
            
            //
            // Save file.
            //
            self::$ConfigMode->projectConfFile->save(self::$ConfigMode->projectConfFilepath);
            
            //
            // Attempt to connect to database.
            //
            self::$ConfigMode->initializeCoreDatabase();
            $dbErrors = self::$ConfigMode->CoreDatabase->flushErrors();
            if (count($dbErrors)) {
              throw new AblePolecat_Mode_Exception('Boot sequence violation: Failed to connect to project database.',
                AblePolecat_Error::BOOT_SEQ_VIOLATION
              );
            }
            
            //
            // Otherwise, install current schema.
            //
            AblePolecat_Database_Schema::install(self::$ConfigMode->CoreDatabase);
          }
          break;
        case 'PUT':
        case 'DELETE':
          break;
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
   * Database functions.
   ********************************************************************************/
  
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
  private function initializeCoreDatabase() {
    
    if (!isset($this->CoreDatabase)) {
      $projectConfFile = $this->getProjectConfFile();
      if (isset($projectConfFile)) {
        $DbNodes = AblePolecat_Dom::getElementsByTagName($projectConfFile, 'database');
        $this->CoreDatabaseConnectionSettings = array();
        $this->CoreDatabaseConnectionSettings['connected'] = FALSE;
        foreach($DbNodes as $key => $Node) {
          if (($Node->getAttribute('id') == self::ACTIVE_CORE_DATABASE_ID) &&
              ($Node->getAttribute('name') == 'polecat') && 
              ($Node->getAttribute('mode') == 'server') && 
              ($Node->getAttribute('use'))) 
          {
            $this->CoreDatabaseConnectionSettings['name'] = $Node->getAttribute('name');
            $this->CoreDatabaseConnectionSettings['mode'] = $Node->getAttribute('mode');
            $this->CoreDatabaseConnectionSettings['use'] = $Node->getAttribute('use');
            foreach($Node->childNodes as $key => $childNode) {
              if($childNode->nodeName == 'dsn') {
                $this->CoreDatabaseConnectionSettings['dsn'] = $childNode->nodeValue;
                break;
              }
            }
          }
        }
          
        if (isset($this->CoreDatabaseConnectionSettings['dsn'])) {
          //
          // Attempt a connection.
          //
          $this->CoreDatabase = AblePolecat_Database_Pdo::wakeup($this->getAgent());
          $DbUrl = AblePolecat_AccessControl_Resource_Locater_Dsn::create($this->CoreDatabaseConnectionSettings['dsn']);
          $this->CoreDatabaseConnectionSettings['connected'] = $this->CoreDatabase->open($this->getAgent(), $DbUrl);
        }
      }
      else {
        throw new AblePolecat_Mode_Exception('Boot sequence violation: Cannot initialize core database. Project configuration file is missing.',
          AblePolecat_Error::BOOT_SEQ_VIOLATION
        );
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
   * @return DOMDOcument The project configuration file.
   */
  public static function getProjectConfFile() {
    
    $projectConfFile = NULL;
    if (isset(self::$ConfigMode)) {
      $projectConfFile = self::$ConfigMode->projectConfFile;
    }
    return $projectConfFile;
  }
  
  /**
   * Returns Full path to project configuration file on local machine.
   *
   * @param bool $asStr If FALSE, return path hierarchy as array, otherwise path as string.
   *
   * @return mixed.
   */
  public static function getProjectConfFilepath($asStr = TRUE) {
    
    static $projectConfFilepathParts;
    $projectConfFilepathParts = array(
      AblePolecat_Server_Paths::getFullPath('usr'), 
      'etc',
      'polecat',
      'conf',
      AblePolecat_Server_Paths::CONF_FILENAME_PROJECT
    );
    $projectConfFilepath = NULL;
    
    if ($asStr) {
        if (isset(self::$ConfigMode)) {
          if (!isset(self::$ConfigMode->projectConfFilepath)) {
            self::$ConfigMode->projectConfFilepath = implode(DIRECTORY_SEPARATOR, $projectConfFilepathParts);
          }
          $projectConfFilepath = self::$ConfigMode->projectConfFilepath;
        }
    }
    else {
      $projectConfFilepath = $projectConfFilepathParts;
    }
    
    return $projectConfFilepath;
  }
  
  /**
   * Create a new project configuration file.
   *
   * @return mixed The newly created project configuration file or FALSE.
   */
  protected static function createProjectConfFile() {
    
    $projectConfFile = FALSE;
    
    if (isset(self::$ConfigMode)) {
      if (isset(self::$ConfigMode->projectConfFile)) {
        $projectConfFile = self::$ConfigMode->projectConfFile;
      }
      else {
        //
        // Create the project configuration file itself.
        //
        self::$ConfigMode->projectConfFile = AblePolecat_Dom::createXmlDocument('polecat');
        self::$ConfigMode->projectConfFile->formatOutput = TRUE;
        
        //
        // project element.
        //
        $projectElement = self::$ConfigMode->projectConfFile->createElement('project');
        $projectElement = AblePolecat_Dom::appendChildToParent(
          $projectElement, 
          self::$ConfigMode->projectConfFile, 
          self::$ConfigMode->projectConfFile->firstChild
        );
        
        //
        // application element
        //
        $applicationElement = self::$ConfigMode->projectConfFile->createElement('application');
        $applicationElement = AblePolecat_Dom::appendChildToParent(
          $applicationElement, 
          self::$ConfigMode->projectConfFile, 
          $projectElement
        );
        
        //
        // locaters element
        //
        $locatersElement = self::$ConfigMode->projectConfFile->createElement('locaters');
        $locatersElement = AblePolecat_Dom::appendChildToParent(
          $locatersElement, 
          self::$ConfigMode->projectConfFile, 
          $applicationElement
        );
        
        //
        // databases element
        //
        $databasesElement = self::$ConfigMode->projectConfFile->createElement('databases');
        $databasesElement = AblePolecat_Dom::appendChildToParent(
          $databasesElement, 
          self::$ConfigMode->projectConfFile, 
          $locatersElement
        );
        
        //
        // core database element
        //
        $databaseElement = self::$ConfigMode->projectConfFile->createElement('database');
        $idAttr = $databaseElement->setAttribute('id', self::ACTIVE_CORE_DATABASE_ID);
        $databaseElement->setIdAttribute('id', TRUE);
        $databaseElement->setAttribute('name', 'polecat');
        $databaseElement->setAttribute('mode', 'server');
        $databaseElement->setAttribute('use', '1');
        $databaseElement = AblePolecat_Dom::appendChildToParent(
          $databaseElement, 
          self::$ConfigMode->projectConfFile, 
          $databasesElement
        );
        
        //
        // dsn element
        //
        $dsnElement = self::$ConfigMode->projectConfFile->createElement('dsn', 'mysql://username:password@localhost/databasename');
        $dsnElement = AblePolecat_Dom::appendChildToParent(
          $dsnElement, 
          self::$ConfigMode->projectConfFile, 
          $databaseElement
        );
        
        //
        // Database schema element.
        //
        $dbSchemaElement = self::$ConfigMode->projectConfFile->createElement('schema', AblePolecat_Database_Schema::getName());
        $dbSchemaElement->setAttribute('id', AblePolecat_Database_Schema::getId());
        $dbSchemaElement = AblePolecat_Dom::appendChildToParent(
          $dbSchemaElement, 
          self::$ConfigMode->projectConfFile, 
          $databaseElement
        );
        
        //
        // @todo: [class]
        //
        
        //
        // @todo: [component]
        //
        
        //
        // @todo: [connector]
        //
        
        //
        // @todo: [resource]
        //
        
        //
        // @todo: [response]
        //
        
        //
        // @todo: [template]
        //
        
        //
        // @todo: class libraries
        //
        
        //
        // Save file.
        //
        $projectConfFilepath = self::getProjectConfFilepath();
        self::$ConfigMode->projectConfFile->save($projectConfFilepath);
        $projectConfFile = self::$ConfigMode->projectConfFile;
      }
    }
    return $projectConfFile;
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
        $sysFilePathParts = self::getProjectConfFilepath(FALSE);
        $sysFilePath = self::getProjectConfFilepath();
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
    
    $this->bootLogFilePath = NULL;
    $this->CoreDatabase = NULL;
    $this->projectConfFile = NULL;
    $this->projectConfFilepath = NULL;
  }
}