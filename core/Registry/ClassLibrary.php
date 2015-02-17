<?php
/**
 * @file      polecat/core/Registry/ClassLibrary.php
 * @brief     Manages registry of third-pary class libraries used by modules.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'ClassLibrary.php')));

class AblePolecat_Registry_ClassLibrary extends AblePolecat_RegistryAbstract {
  
  /**
   * @var AblePolecat_Registry_ClassLibrary Singleton instance.
   */
  private static $Registry = NULL;
  
  /**
   * @var List of Able Polecat modules.
   */
  private $ClassLibraries = NULL;
  
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
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Registry_ClassLibrary Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Registry)) {
      try {
        self::$Registry = new AblePolecat_Registry_ClassLibrary($Subject);
      }
      catch (Exception $Exception) {
        self::$Registry = NULL;
        throw new AblePolecat_Registry_Exception($Exception->getMessage(), AblePolecat_Error::WAKEUP_FAIL);
      }
    }
    return self::$Registry;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Database_InstallerInterface.
   ********************************************************************************/
   
  /**
   * Install class registry on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public static function install(AblePolecat_DatabaseInterface $Database) {
    //
    // Load master project configuration file.
    //
    $masterProjectConfFile = AblePolecat_Mode_Config::getMasterProjectConfFile();
    
    //
    // Get package (class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'package');
    $applicationNode = $Nodes->item(0);
    if (isset($applicationNode)) {
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::create();
      $ClassLibraryRegistration->id = $applicationNode->getAttribute('id');
      $ClassLibraryRegistration->name = $applicationNode->getAttribute('name');
      $ClassLibraryRegistration->libType = strtolower($applicationNode->getAttribute('type'));
      $ClassLibraryRegistration->libFullPath = AblePolecat_Server_Paths::getFullPath('src');
      $ClassLibraryRegistration->useLib = '1';
      $ClassLibraryRegistration->save($Database);
    }
    else {
      //
      // @todo: this type of schema checking should be done by implementing an XML schema.
      //
      $message = 'project.xml must contain an package node.';
      trigger_error($message, E_USER_ERROR);
    }

    //
    // Create DML statements for classes.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'classLibrary');
    foreach($Nodes as $key => $Node) {
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::import($Node);
      if (isset($ClassLibraryRegistration)) {
        $ClassLibraryRegistration->save($Database);
      }
      
      //
      // If the class library is a module, load the corresponding project 
      // configuration file and register any dependent class libraries.
      //
      if ($ClassLibraryRegistration->libType === 'mod') {
        $modConfFilePath = implode(DIRECTORY_SEPARATOR, array(
          $ClassLibraryRegistration->libFullPath,
          'etc',
          'polecat',
          'conf',
          AblePolecat_Server_Paths::CONF_FILENAME_PROJECT
        ));
        $modConfFile = new DOMDocument();
        $modConfFile->load($modConfFilePath);
        
        $modNodes = AblePolecat_Dom::getElementsByTagName($modConfFile, 'classLibrary');
        foreach($modNodes as $key => $modNode) {
          $modClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::import($modNode);
          if (isset($modClassLibraryRegistration)) {
            $modClassLibraryRegistration->save($Database);
          }
        }
      }
    }
  }
  
  /**
   * Update current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if update fails.
   */
  public static function update(AblePolecat_DatabaseInterface $Database) {
    
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
    /**
   * Extends constructor.
   */
  protected function initialize() {
    //
    // Supported modules.
    //
    $this->ClassLibraries = array();
  }
}