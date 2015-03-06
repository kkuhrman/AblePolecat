<?php
/**
 * @file      polecat/core/Registry/Entry/Template.php
 * @brief     Encapsulates record of a template registered in [template].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_TemplateInterface extends AblePolecat_Registry_EntryInterface {  
  /**
   * @return string.
   */
  public function getArticleId();
  
  /**
   * @return string.
   */
  public function getDocType();
  
  /**
   * @return string.
   */
  public function getFullPath();
  
  /**
   * @return string.
   */
  public function getTemplateScope();
  
  /**
   * @return string.
   */
  public function getThemeName();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Template extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_TemplateInterface {
  
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
    
    if ($name == 'fullPath') {
      $this->fileStat = stat($value);
      if ($this->fileStat && isset($this->fileStat['mtime'])) {
        parent::__set('lastModifiedTime', $this->fileStat['mtime']);
      }
      else {
        AblePolecat_Command_Chain::triggerError("Failed to retrieve file stats on $value.");
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
    $RegistryEntry = new AblePolecat_Registry_Entry_Template();
    
    //
    // Check method arguments for database record.
    //
    $args = func_get_args();
    if (isset($args[0]) && is_array($args[0])) {
      $Record = $args[0];
      isset($Record['id']) ? $RegistryEntry->id = $Record['id'] : NULL;
      isset($Record['name']) ? $RegistryEntry->name = $Record['name'] : NULL;
      isset($Record['themeName']) ? $RegistryEntry->themeName = $Record['themeName'] : NULL;
      isset($Record['templateScope']) ? $RegistryEntry->templateScope = $Record['templateScope'] : NULL;
      isset($Record['articleId']) ? $RegistryEntry->articleId = $Record['articleId'] : NULL;
      isset($Record['docType']) ? $RegistryEntry->docType = $Record['docType'] : NULL;
      isset($Record['fullPath']) ? $RegistryEntry->fullPath = $Record['fullPath'] : NULL;
      isset($Record['lastModifiedTime']) ? $RegistryEntry->lastModifiedTime = $Record['lastModifiedTime'] : NULL;
    }
    return $RegistryEntry;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * Create the registry entry object and populate with given DOMNode data.
   *
   * @param DOMNode $Node DOMNode encapsulating registry entry.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function import(DOMNode $Node) {
    
    $RegistryEntry = AblePolecat_Registry_Entry_Template::create();
    $RegistryEntry->id = $Node->getAttribute('id');
    $RegistryEntry->name = $Node->getAttribute('name');
    foreach($Node->childNodes as $key => $childNode) {
      switch ($childNode->nodeName) {
        default:
          break;
        case 'polecat:articleId':
          $RegistryEntry->articleId = $childNode->nodeValue;
          break;
        case 'polecat:docType':
          $RegistryEntry->docType = $childNode->nodeValue;
          break;
        case 'polecat:templateScope':
          $RegistryEntry->templateScope = $childNode->nodeValue;
          break;
        case 'polecat:themeName':
          $RegistryEntry->themeName = $childNode->nodeValue;
          break;
        case 'polecat:path':
          //
          // NOTE: path is only set in case of non-standard path given (i.e. full path).
          //
          $sanitizePath = AblePolecat_Server_Paths::sanitizePath($childNode->nodeValue);
          if (AblePolecat_Server_Paths::verifyFile($sanitizePath)) {
            $RegistryEntry->fullPath = $sanitizePath;
          }
          break;
      }
    }
    !isset($RegistryEntry->themeName) ? $RegistryEntry->themeName = 'default' : NULL;
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
    // @todo: export [template] registry entry.
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
          'themeName', 
          'templateScope', 
          'articleId', 
          'docType', 
          'fullPath', 
          'lastModifiedTime')->
        from('template')->
        where(sprintf("`id` = '%s'", $primaryKey));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_Template::create($registrationInfo[0]);
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
        'themeName', 
        'templateScope', 
        'articleId', 
        'docType', 
        'fullPath', 
        'lastModifiedTime')->
      into('template')->
      values(
        $this->getId(),
        $this->getName(),
        $this->getThemeName(), 
        $this->getTemplateScope(), 
        $this->getArticleId(), 
        $this->getDocType(), 
        $this->getFullPath(),
        $this->getLastModifiedTime()
      );
    return $this->executeDml($sql, $Database);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_TemplateInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getArticleId() {
    return $this->getPropertyValue('articleId');
  }
  
  /**
   * @return string.
   */
  public function getDocType() {
    return $this->getPropertyValue('docType');
  }
  
  /**
   * @return string.
   */
  public function getFullPath() {
    return $this->getPropertyValue('fullPath');
  }
  
  /**
   * @return string.
   */
  public function getTemplateScope() {
    return $this->getPropertyValue('templateScope');
  }
  
  /**
   * @return string.
   */
  public function getThemeName() {
    return $this->getPropertyValue('themeName');
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