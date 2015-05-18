<?php
/**
 * @file      polecat/core/Registry/Entry/Class.php
 * @brief     Encapsulates record of a resource registered in [class].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ClassInterface extends AblePolecat_Registry_EntryInterface {  
  
  /**
   * @return string.
   */
  public function getClassLibraryId();
    
  /**
   * @return string.
   */
  public function getClassFullPath();
  
  /**
   * @return string.
   */
  public function getClassFactoryMethod();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Class extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ClassInterface {
  
  /**
   * @var Array File statistics from stat().
   */
  private $fileStat;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * PHP magic method is run when writing data to inaccessible properties.
   *
   * @param string $name  Name of property to set.
   * @param mixed  $value Value to assign to given property.
   */
  public function __set($name, $value) {
    
    if ($name == 'classFullPath') {
      if (file_exists($value)) {
        $this->fileStat = stat($value);
      }
      if (isset($this->fileStat) && isset($this->fileStat['mtime'])) {
        parent::__set('lastModifiedTime', $this->fileStat['mtime']);
      }
      else {
        AblePolecat_Command_Log::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), 
          "Failed to retrieve file stats on $value.", AblePolecat_LogInterface::WARNING);
        $value = NULL;
      }
    }
    parent::__set($name, $value);
  }
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    //
    // Create instance of class.
    //
    $RegistryEntry = new AblePolecat_Registry_Entry_Class();
    
    //
    // Check method arguments for database record.
    //
    $args = func_get_args();
    if (isset($args[0]) && is_array($args[0])) {
      $Record = $args[0];
      isset($Record['id']) ? $RegistryEntry->id = $Record['id'] : NULL;
      isset($Record['name']) ? $RegistryEntry->name = $Record['name'] : NULL;
      isset($Record['classLibraryId']) ? $RegistryEntry->classLibraryId = $Record['classLibraryId'] : NULL;
      isset($Record['classFullPath']) ? $RegistryEntry->classFullPath = $Record['classFullPath'] : NULL;
      isset($Record['classFactoryMethod']) ? $RegistryEntry->classFactoryMethod = $Record['classFactoryMethod'] : NULL;
      // isset($Record['lastModifiedTime']) ? $RegistryEntry->lastModifiedTime = $Record['lastModifiedTime'] : NULL;
    }
    return $RegistryEntry;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * Create the registry entry object and populate with given DOMNode data.
   *
   * Some properties, notably classLibraryId and classFullPath, are dependent on 
   * the parent node and are not set herein.
   *
   * @param DOMNode $Node DOMNode encapsulating registry entry.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function import(DOMNode $Node) {
    
    //
    // @todo: throw exception if Node does not comply with schema
    //
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = $Node->getAttribute('id');
    $RegistryEntry->name = $Node->getAttribute('name');
    foreach($Node->childNodes as $key => $childNode) {
      switch ($childNode->nodeName) {
        default:
          break;
        case 'polecat:classFactoryMethod':
          $RegistryEntry->classFactoryMethod = $childNode->nodeValue;
          break;
        case 'polecat:path':
          //
          // NOTE: path is only set in case of non-standard path given (i.e. full path).
          //
          $sanitizePath = AblePolecat_Server_Paths::sanitizePath($childNode->nodeValue);
          if (AblePolecat_Server_Paths::verifyFile($sanitizePath)) {
            $ClassRegistration->classFullPath = $sanitizePath;
          }
          break;
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
    // @todo: export class registry entry.
    //
  }
  
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface OR NULL.
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
          'classLibraryId', 
          'classFullPath', 
          'classFactoryMethod', 
          'lastModifiedTime')->
        from('class')->
        where(sprintf("`id` = '%s'", $primaryKey));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
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
        'classLibraryId', 
        'classFullPath', 
        'classFactoryMethod', 
        'lastModifiedTime')->
      into('class')->
      values(
        $this->getId(), 
        $this->getName(), 
        $this->getClassLibraryId(), 
        $this->getClassFullPath(), 
        $this->getClassFactoryMethod(), 
        $this->getLastModifiedTime()
      );
    return $this->executeDml($sql, $Database);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ClassInterface.
   ********************************************************************************/
  
  /**
   * @return string.
   */
  public function getClassLibraryId() {
    return $this->getPropertyValue('classLibraryId');
  }
    
  /**
   * @return string.
   */
  public function getClassFullPath() {
    return $this->getPropertyValue('classFullPath');
  }
  
  /**
   * @return string.
   */
  public function getClassFactoryMethod() {
    return $this->getPropertyValue('classFactoryMethod');
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Output class state to debug log.
   */
  public function dumpState() {
    $message = sprintf("REGISTRY: name=%s, id=%s; classLibraryId=%s; classFullPath=%s, classFactoryMethod=%s, lastModifiedTime=%d",
      $this->getName(),
      $this->getId(),
      $this->getClassLibraryId(),
      $this->getClassFullPath(),
      $this->getClassFactoryMethod(),
      $this->getLastModifiedTime()
    );
    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}