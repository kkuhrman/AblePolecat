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
    }
    return $RegistryEntry;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * Generate registry entry data from project configuration file element(s).
   *
   * @param DOMNode $Node Registry entry data from project configuration file.
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public static function import(DOMNode $Node) {
    
    $RegistryEntries = array();
    
    if (is_a($Node, 'DOMElement') && ($Node->tagName == 'polecat:template') && $Node->hasChildNodes()) {
      $templateId = $Node->getAttribute('id');
      $themeName = NULL;
      $templateScope = NULL;
      $classId = NULL;
      $className = NULL;
      $docType = NULL;
      $fullPath = NULL;
      $lastModifiedTime = 0;
      foreach($Node->childNodes as $key => $childNode) {
        switch ($childNode->nodeName) {
          default:
            break;
          case 'polecat:themeName':
            $themeName = $childNode->nodeValue;
            break;
          case 'polecat:templateScope':
            $templateScope = $childNode->nodeValue;
            break;
          case 'polecat:articleId':
            //
            // Verify class (articleId) reference.
            //
            $classId = $childNode->nodeValue;
            $ClassRegistration = AblePolecat_Registry_Class::wakeup()->
              getRegistrationById($classId);
            if (isset($ClassRegistration)) {
              $className = $ClassRegistration->getName();
              $lastModifiedTime = $ClassRegistration->getLastModifiedTime();
            }
            else {
              $message = sprintf("template article %s references invalid class id %s.",
                $classId,
                $className
              );
              $RegistryEntry = NULL;
              AblePolecat_Command_Chain::triggerError($message);
            }
            
            break;
          case 'polecat:docType':
            $docType = $childNode->nodeValue;
            break;
          case 'polecat:path':
            $fullPath = AblePolecat_Server_Paths::sanitizePath($childNode->nodeValue);
            break;
        }
      }
      
      //
      // Verify theme name.
      //
      !isset($themeName) ? $themeName = 'default' : NULL;
      
      //
      // Verify template scope.
      //
      $articleType = FALSE;
      if (is_subclass_of($className, 'AblePolecat_Message_ResponseInterface')) {
        $articleType = 'AblePolecat_Message_ResponseInterface';
      }
      else {
        if (is_subclass_of($className, 'AblePolecat_ComponentInterface')) {
          $articleType = 'AblePolecat_ComponentInterface';
        }
      }
      if ($articleType) {
        if (!isset($templateScope)) {
          switch ($articleType) {
            default:
              if ($templateScope != 'document') {
                $message = sprintf("Incompatible template scope (%s) given for template %s. Articles implementing %s must have a template scope of 'document'.",
                  $templateScope,
                  $templateId,
                  $articleType
                );
                AblePolecat_Command_Chain::triggerError($message);
              }
              break;
            case 'AblePolecat_ComponentInterface':
              if ($templateScope != 'element') {
                $message = sprintf("Incompatible template scope (%s) given for template %s. Articles implementing %s must have a template scope of 'element'.",
                  $templateScope,
                  $templateId,
                  $articleType
                );
                AblePolecat_Command_Chain::triggerError($message);
              }
              break;
          }
        }
        else {
          switch ($articleType) {
            default:
              $templateScope = 'document';
              break;
            case 'AblePolecat_ComponentInterface':
              $templateScope = 'element';
              break;
          }
        }
      }
      else {
        $message = sprintf("Invalid article id (%s) given for template %s. %s does not implement AblePolecat_Message_ResponseInterface or AblePolecat_ComponentInterface.",
          $articleType,
          $templateId,
          $className
        );
        AblePolecat_Command_Chain::triggerError($message);
      }
      
      //
      // Verify template path.
      //
      if (!AblePolecat_Server_Paths::verifyFile($fullPath)) {
        //
        // Conventional (relative) path is given.
        //
        $fullPathTemp = implode(DIRECTORY_SEPARATOR, 
          array(
            ABLE_POLECAT_VAR, 
            'www', 
            'htdocs', 
            'theme', 
            $themeName, 
            $fullPath,
          )
        );
        $sanitizePath = AblePolecat_Server_Paths::sanitizePath($fullPathTemp);
        if (AblePolecat_Server_Paths::verifyFile($sanitizePath)) {
          $fullPath = $sanitizePath;
        }
        else {
          $message = sprintf("Invalid path given for template %s (%s).",
            $templateName,
            $fullPath
          );
          AblePolecat_Command_Chain::triggerError($message);
        }
      }
      
      //
      // Generate registry entry/entries.
      //
      switch ($articleType) {
        default:
          break;
        case 'AblePolecat_Message_ResponseInterface':
          $sql = __SQL()->          
            select(
              'id')->
            from('response')->
            where(sprintf("`classId` = '%s'", $classId));
          $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
          if ($CommandResult->success()) {
            $responses = $CommandResult->value();
            foreach($responses as $key => $response) {
              if(isset($response['id'])) {
                $RegistryEntry = AblePolecat_Registry_Entry_Template::create();
                $RegistryEntry->id = self::generateUUID();
                $RegistryEntry->name = $Node->getAttribute('name');
                $RegistryEntry->themeName = $themeName;
                $RegistryEntry->templateScope = $templateScope;
                $RegistryEntry->articleId = $response['id'];
                $RegistryEntry->docType = $docType;
                $RegistryEntry->fullPath = $fullPath;
                $RegistryEntries[] = $RegistryEntry;
              }
            }
          }
          break;
        case 'AblePolecat_ComponentInterface':
          $RegistryEntry = AblePolecat_Registry_Entry_Template::create();
          $RegistryEntry->id = self::generateUUID();
          $RegistryEntry->name = $Node->getAttribute('name');
          $RegistryEntry->themeName = $themeName;
          $RegistryEntry->templateScope = $templateScope;
          $RegistryEntry->articleId = $classId;
          $RegistryEntry->docType = $docType;
          $RegistryEntry->fullPath = $fullPath;
          $RegistryEntries[] = $RegistryEntry;
          break;
      }
    }
    return $RegistryEntries;
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