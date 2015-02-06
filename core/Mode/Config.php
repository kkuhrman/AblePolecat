<?php
/**
 * @file      polecat/core/Mode/Config.php
 * @brief     Configuration mode checks critical settings, attempts to fix problems.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode.php')));

class AblePolecat_Mode_Config extends AblePolecat_ModeAbstract {
  
  const UUID = '3599ce6f-ad72-11e4-976e-0050569e00a2';
  const NAME = 'AblePolecat_Mode_Config';
  
  /**
   * AblePolecat_Mode_Config Instance of singleton.
   */
  private static $ConfigMode;
  
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
      // Set project configuration file full path for local machine.
      //
      $projectConfFilepathParts = array(
        AblePolecat_Server_Paths::getFullPath('usr'), 
        'etc',
        'polecat',
        'conf',
        AblePolecat_Server_Paths::CONF_FILENAME_PROJECT
      );
      $projectConfFilepath = implode(DIRECTORY_SEPARATOR, $projectConfFilepathParts);
      
      //
      // Peek at HTTP request.
      //
      isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
      switch ($method) {
        default:
          break;
        case 'GET':
          if (AblePolecat_Server_Paths::verifyFile($projectConfFilepath)) {
            //
            // Project configuration file exists, attempt to connect to database.
            //
          }
          else {
            //
            // Project configuration file does not exist, attempt to initialize it.
            //
            $projectConfFilepath = '';
            $projectConfFilepathPartsCount = count($projectConfFilepathParts) - 1;
            foreach($projectConfFilepathParts as $key => $pathPart) {
              $isDir = ($key < $projectConfFilepathPartsCount);
              if ($isDir) {
                //
                // Create the project configuration file path hierarchy.
                //
                $projectConfFilepath .= $pathPart;
                AblePolecat_Server_Paths::touch($projectConfFilepath, $isDir);
                $projectConfFilepath .= DIRECTORY_SEPARATOR;
              }
              else {
                //
                // Create the project configuration file itself.
                //
                $projectConfFilepath .= $pathPart;
                $projectConfFile = AblePolecat_Dom::createXmlDocument('polecat');
                
                //
                // project element.
                //
                $projectElement = $projectConfFile->createElement('project');
                $projectElement = AblePolecat_Dom::appendChildToParent(
                  $projectElement, 
                  $projectConfFile, 
                  $projectConfFile->firstChild
                );
                
                //
                // application element
                //
                $applicationElement = $projectConfFile->createElement('application');
                $applicationElement = AblePolecat_Dom::appendChildToParent(
                  $applicationElement, 
                  $projectConfFile, 
                  $projectElement
                );
                
                //
                // locaters element
                //
                $locatersElement = $projectConfFile->createElement('locaters');
                $locatersElement = AblePolecat_Dom::appendChildToParent(
                  $locatersElement, 
                  $projectConfFile, 
                  $applicationElement
                );
                
                //
                // databases element
                //
                $databasesElement = $projectConfFile->createElement('databases');
                $databasesElement = AblePolecat_Dom::appendChildToParent(
                  $databasesElement, 
                  $projectConfFile, 
                  $locatersElement
                );
                
                //
                // core database element
                //
                $databaseElement = $projectConfFile->createElement('database');
                $idAttr = $databaseElement->setAttribute('id', 'core');
                $databaseElement->setIdAttribute('id', TRUE);
                $databaseElement->setAttribute('name', 'polecat');
                $databaseElement->setAttribute('mode', 'server');
                $databaseElement->setAttribute('use', '1');
                $databaseElement = AblePolecat_Dom::appendChildToParent(
                  $databaseElement, 
                  $projectConfFile, 
                  $databasesElement
                );
                
                //
                // dsn element
                //
                $dsnElement = $projectConfFile->createElement('dsn', 'mysql://username:password@localhost/databasename');
                $dsnElement = AblePolecat_Dom::appendChildToParent(
                  $dsnElement, 
                  $projectConfFile, 
                  $databaseElement
                );
                
                //
                // Save file.
                //
                $projectConfFile->save($projectConfFilepath);
              }
            }
          }
          break;
        case 'POST':
          break;
        case 'PUT':
        case 'DELETE':
          break;
      }
      AblePolecat_Debug::kill($method);
      
        
      AblePolecat_Debug::kill($projectConfFilepath);
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
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    
    parent::initialize();
  }
}