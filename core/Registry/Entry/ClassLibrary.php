<?php
/**
 * @file      polecat/core/Registry/Entry/ClassLibrary.php
 * @brief     Encapsulates record of a resource registered in [classlib].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ClassLibraryInterface extends AblePolecat_Registry_EntryInterface {  
    
  /**
   * @return string.
   */
  public function getClassLibraryType();
  
  /**
   * @return string.
   */
  public function getClassLibraryFullPath();
  
  /**
   * @return string.
   */
  public function getClassLibraryUseFlag();  
  
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_ClassLibrary extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ClassLibraryInterface {
  
  /**
   * @var Array File statistics from stat().
   */
  private $fileStat;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_ClassLibrary();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * PHP magic method is run when writing data to inaccessible properties.
   *
   * @param string $name  Name of property to set.
   * @param mixed  $value Value to assign to given property.
   */
  public function __set($name, $value) {
    
    if ($name == 'libFullPath') {
      $this->fileStat = stat($value);
      if ($this->fileStat && isset($this->fileStat['mtime'])) {
        parent::__set('lastModifiedTime', $this->fileStat['mtime']);
      }
      else {
        throw new AblePolecat_Registry_Exception("Failed to retrieve file stats on $value.");
      }
    }
    parent::__set($name, $value);
  }
  
  /**
   * Create the registry entry object and populate with given DOMNode data.
   *
   * @param DOMNode $Node DOMNode encapsulating registry entry.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function import(DOMNode $Node) {
    
    $ClassLibraryRegistration = NULL;
    
    $useLib = $Node->getAttribute('use');
    if ($useLib === '1') {
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::create();
      $ClassLibraryRegistration->id = $Node->getAttribute('id');
      $ClassLibraryRegistration->name = $Node->getAttribute('name');
      $ClassLibraryRegistration->libType = strtolower($Node->getAttribute('type'));
      $ClassLibraryRegistration->useLib = $useLib;
      foreach($Node->childNodes as $key => $childNode) {
        switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:path':
            //
            // Check for path at usr\lib or usr\mod.
            //
            $rawPath = implode(DIRECTORY_SEPARATOR, array(
              AblePolecat_Server_Paths::getFullPath('usr'),
              $ClassLibraryRegistration->libType,
              $childNode->nodeValue
            ));
            $checkSanitizedPath = AblePolecat_Server_Paths::sanitizePath($rawPath);
            if (!AblePolecat_Server_Paths::verifyDirectory($checkSanitizedPath)) {
              //
              // Check if this is a full path.
              //
              $checkSanitizedPath = AblePolecat_Server_Paths::sanitizePath($childNode->nodeValue);
              if (!AblePolecat_Server_Paths::verifyDirectory($checkSanitizedPath)) {
                $message = sprintf("Invalid path given for active class library %s (%s).",
                  $ClassLibraryRegistration->name,
                  $childNode->nodeValue
                );
                AblePolecat_Registry_ClassLibrary::triggerError($message);
              }
            }
            $ClassLibraryRegistration->libFullPath = $checkSanitizedPath;
            break;
        }
      }
    }
    return $ClassLibraryRegistration;
  }
  
  /**
   * Create DOMNode and populate with registry entry data .
   *
   * @param DOMDocument $Document Registry entry will be exported to this DOM Document.
   * @param DOMElement $Parent Registry entry will be appended to this DOM Element.
   *
   * @return DOMElement Exported element or NULL.
   */
  public function export(DOMDocument $Document, DOMElement $Parent) {
    //
    // @todo: export class library registry entry.
    //
  }
  
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey) {
    
    $ClassLibraryRegistration = NULL;
    
    if (self::validatePrimaryKey($primaryKey)) {
      //
      // Create registry object and initialize primary key.
      //
      $ClassLibraryRegistration = AblePolecat_Registry_Entry_ClassLibrary::create();
      isset($primaryKey['id']) ? $ClassLibraryRegistration->id = $primaryKey['id'] : $ClassLibraryRegistration->id = $primaryKey;
      
      //
      // Generate and execute SELECT statement.
      //
      $sql = __SQL()->          
        select(
          'id', 
          'name', 
          'libType', 
          'libFullPath', 
          'useLib', 
          'lastModifiedTime')->
        from('lib')->
        where(sprintf("`id` = '%s' AND `statusCode` = %d", $primaryKey));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          isset($registrationInfo[0]['name']) ? $ClassLibraryRegistration->name = $registrationInfo[0]['name'] : NULL;
          isset($registrationInfo[0]['libType']) ? $ClassLibraryRegistration->libType = $registrationInfo[0]['libType'] : NULL;
          isset($registrationInfo[0]['libFullPath']) ? $ClassLibraryRegistration->libFullPath = $registrationInfo[0]['libFullPath'] : NULL;
          isset($registrationInfo[0]['useLib']) ? $ClassLibraryRegistration->useLib = $registrationInfo[0]['useLib'] : NULL;
          isset($registrationInfo[0]['lastModifiedTime']) ? $ClassLibraryRegistration->lastModifiedTime = $registrationInfo[0]['lastModifiedTime'] : NULL;
        }
      }
    }
    return $ClassLibraryRegistration; 
  }
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames() {
    return array(0 => 'id');
  }
  
  /**
   * Update or insert registration record.
   *
   * If the encapsulated registration exists, based on id property, it will be updated
   * to reflect object state. Otherwise, a new registration record will be created.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function save(AblePolecat_DatabaseInterface $Database = NULL) {
    $sql = __SQL()->          
      insert(
        'id', 
        'name', 
        'libType', 
        'libFullPath', 
        'useLib', 
        'lastModifiedTime')->
      into('lib')->
      values(
        $this->getId(), 
        $this->getName(), 
        $this->getClassLibraryType(), 
        $this->getClassLibraryFullPath(), 
        $this->getClassLibraryUseFlag(), 
        $this->getLastModifiedTime()
      );
    $this->executeDml($sql, $Database);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ClassLibraryInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getClassLibraryType() {
    return $this->getPropertyValue('libType');
  }
  
  /**
   * @return string.
   */
  public function getClassLibraryFullPath() {
    return $this->getPropertyValue('libFullPath');
  }
  
  /**
   * @return string.
   */
  public function getClassLibraryUseFlag() {
    return $this->getPropertyValue('useLib');
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}