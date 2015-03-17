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
    //
    // Create instance of class.
    //
    $RegistryEntry = new AblePolecat_Registry_Entry_ClassLibrary();
    
    //
    // Check method arguments for database record.
    //
    $args = func_get_args();
    if (isset($args[0]) && is_array($args[0])) {
      $Record = $args[0];
      isset($Record['id']) ? $RegistryEntry->id = $Record['id'] : NULL;
      isset($Record['name']) ? $RegistryEntry->name = $Record['name'] : NULL;
      isset($Record['libType']) ? $RegistryEntry->libType = $Record['libType'] : NULL;
      isset($Record['libFullPath']) ? $RegistryEntry->libFullPath = $Record['libFullPath'] : NULL;
      isset($Record['useLib']) ? $RegistryEntry->useLib = $Record['useLib'] : NULL;
    }
    return $RegistryEntry;
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
    
    $RegistryEntry = NULL;
    
    $useLib = $Node->getAttribute('use');
    if ($useLib === '1') {
      $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create();
      $RegistryEntry->id = $Node->getAttribute('id');
      $RegistryEntry->name = $Node->getAttribute('name');
      $RegistryEntry->libType = strtolower($Node->getAttribute('type'));
      $RegistryEntry->useLib = $useLib;
      foreach($Node->childNodes as $key => $childNode) {
        switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:path':
            //
            // Check for path at usr\lib or usr\mod.
            //
            $rawPath = '';
            switch ($RegistryEntry->libType) {
              default:
                $rawPath = implode(DIRECTORY_SEPARATOR, array(
                  AblePolecat_Server_Paths::getFullPath('usr'),
                  $RegistryEntry->libType,
                  $childNode->nodeValue
                ));
                break;
              case 'app':
                $rawPath = AblePolecat_Server_Paths::getFullPath('src');
                break;
            }
            
            $checkSanitizedPath = AblePolecat_Server_Paths::sanitizePath($rawPath);
            switch($RegistryEntry->libType) {
              default:
                break;
              case 'mod':
                //
                // @todo: this forces developer to put code under ProjectRoot/usr/src.
                //
                $checkSanitizedPath = implode(DIRECTORY_SEPARATOR, array($checkSanitizedPath, 'usr', 'src'));
                break;
            }
            if (!AblePolecat_Server_Paths::verifyDirectory($checkSanitizedPath)) {
              //
              // Check if this is a full path.
              //
              $checkSanitizedPath = AblePolecat_Server_Paths::sanitizePath($childNode->nodeValue);
              if (!AblePolecat_Server_Paths::verifyDirectory($checkSanitizedPath)) {
                $message = sprintf("Invalid path given for active class library %s (%s) - %s.",
                  $RegistryEntry->name,
                  $childNode->nodeValue,
                  $checkSanitizedPath
                );
                AblePolecat_Command_Chain::triggerError($message);
              }
            }
            $RegistryEntry->libFullPath = $checkSanitizedPath;
            break;
        }
      }
    }
    return $RegistryEntry;
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
    
    $RegistryEntry = NULL;
    
    $primaryKey = self::validatePrimaryKey($primaryKey);
    if ($primaryKey) {
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
        where(sprintf("`id` = '%s'", $primaryKey));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create($registrationInfo[0]);
        }
      }
    }
    return $RegistryEntry; 
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
      replace(
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