<?php
/**
 * @file: Dom.php
 * Uses DOMDocument to merge system configuration settings from multiple sources.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Conf.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Open.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Read.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Write.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server', 'Paths.php')));

class AblePolecat_Conf_Dom extends AblePolecat_ConfAbstract {
  
  const UUID              = 'fff28c77-3e91-4f3b-98d1-1e3a5347f1df';
  const NAME              = 'Able Polecat Configuration Settings DOM Document';
  
  /**
   * @var DOMDocument System-wide configuration settings.
   */
  private $Document = NULL;
  
  /**
   * @var Variables extracted from document.
   */
  private $Variables = NULL;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    
    //
    // Create DOM document (container).
    //
    $this->Document = new DOMDocument('1.0', 'UTF-8');
    $this->Document->formatOutput = true;
    $RootElement = $this->Document->createElement(self::ELEMENT_ROOT);
    $RootElement = $this->Document->appendChild($RootElement);
    $CoreElement = $this->Document->createElement(self::ELEMENT_CORE);
    $CoreElement = $RootElement->appendChild($CoreElement);
  }
  
  /**
   * Attempts to merge (imports) an XML sub-tree from given file into the current configuration.
   *
   * @param string $source Name of a file containing XML to load/import or well-formed XML string.
   * @param string $tagname Name of the top-level element to import from sub-tree.
   * @param string $parent Name of the element under which to insert sub-tree.
   * @param bool $recursive If TRUE, recursively import entire sub-tree under $tagname.
   *
   * @throw AblePolecat_Conf_Exception.
   */
  protected function mergePartialXml($source, $tagname, $parent = self::ELEMENT_ROOT, $recursive = true) {
    
    $Element = NULL;
    
    if (file_exists($source)) {
      //
      // source is a file
      //
      // die('roll the bones');
      $XmlPart = @DOMDocument::load($source);
      if ($XmlPart) {
        $Element = $XmlPart->getElementsByTagName($tagname)->item(0);
      }
    }
    else {
      //
      // source is XML
      //
      $XmlPart = new DOMDocument('1.0', 'UTF-8');
      $XmlPart->formatOutput = true;
      $XmlPart->loadXml($source);
      $Element = $XmlPart->getElementsByTagName($tagname)->item(0);
    }
    if (!isset($Element)) {
      throw new AblePolecat_Conf_Exception("Failed to merge element given by $tagname with system configuration.",
        AblePolecat_Error::BOOTSTRAP_CONFIG
      );
    }
    $ParentElement = $this->Document->getElementsByTagName($parent)->item(0);
    $Node = $this->Document->importNode($Element, $recursive);
    $ParentElement->appendChild($Node);
  }
  
  /**
   * Return unique, system-wide identifier for security resource.
   *
   * @return string Resource identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security resource.
   *
   * @return string Resource name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /**
   * @return Array Core class registry.
   */
  public function getCoreClasses() {
  }
  
  /**
   * @return Array Core database connection settings.
   */
  public function getCoreDatabaseConf() {
    if (!isset($this->Document)) {
      throw new AblePolecat_Conf_Exception(
        'System configuration document is NULL.',
        AblePolecat_Error::BOOTSTRAP_CONFIG
      );
    }
    if (!isset($this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB])) {
      !isset($this->Variables[self::ELEMENT_HOST]) ? $this->Variables[self::ELEMENT_HOST] = array() : NULL;
      $this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB] = array();
      
      //
      // More than one application database can be defined in server conf file. However, only ONE
      // application database can be active per server mode. 
      // If 'mode' attribute is empty, polecat will assume any mode; otherwise, database is defined 
      // for given mode only. 
      // The 'use' attribute indicates that the database should be loaded for the mode(s) given by 
      // the 'mode' attribute. 
      // Polecat will scan database definitions until it finds one with mode/use attributes combined 
      // in such a way that directs it to use the database for the current server mode.
      //
      $Elements = $this->Document->getElementsByTagName(self::ELEMENT_DB);
      foreach($Elements as $elementKey => $Element) {
        //
        // check db mode flag
        //
        $dbMode = $Element->getAttribute(self::ATTRIBUTE_MODE);
        $dbModeCheck = TRUE;
        if (strlen($dbMode) && (strcasecmp($dbMode, 'any') != 0)) {
          //
          // database is defined only for given mode
          //
          if (strcasecmp($dbMode, AblePolecat_Server::getBootMode()) != 0) {
            $dbModeCheck = FALSE;
          }
        }
        
        //
        // check db use flag
        //
        $dbUse = $Element->getAttribute(self::ATTRIBUTE_USE);
        if ($dbModeCheck) {
          $this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB][self::ATTRIBUTE_MODE] = 
            $dbMode;
          $this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB][self::ATTRIBUTE_USE] = 
            $dbUse;
          $this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB][self::ATTRIBUTE_NAME] = 
            $Element->getAttribute(self::ATTRIBUTE_NAME);
          $this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB][self::ATTRIBUTE_ID] = 
            $Element->getAttribute(self::ATTRIBUTE_ID);
          // $this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB][self::ATTRIBUTE_CONNECTED] = 
            // FALSE;
          
          foreach($Element->childNodes as $nodeKey => $Node) {
            if ($Node->nodeName != '#text') {
              $this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB][$Node->nodeName] = 
                $Node->nodeValue;
            }
          }
        }
      }
    }
    return $this->Variables[self::ELEMENT_HOST][self::ELEMENT_DB];
  }
  
  /**
   * @return Array List of supported interfaces.
   */
  public function getCoreInterfaces() {
  }
  
  /**
   * @return Array Core version.
   */
  public function getCoreVersion() {
    
    if (!isset($this->Document)) {
      throw new AblePolecat_Conf_Exception(
        'System configuration document is NULL.',
        AblePolecat_Error::BOOTSTRAP_CONFIG
      );
    }
    if (!isset($this->Variables[self::ELEMENT_CORE][self::ELEMENT_VERSION])) {
      !isset($this->Variables[self::ELEMENT_CORE]) ? $this->Variables[self::ELEMENT_CORE] = array() : NULL;
      $this->Variables[self::ELEMENT_CORE][self::ELEMENT_VERSION] = array();
      
      //
      // Core version.
      //
      $Elements = $this->Document->getElementsByTagName(self::ELEMENT_VERSION);
      foreach($Elements as $elementKey => $Element) {
        if($elementKey) {
          //
          // @todo: config file is possibly corrupted
          //
          AblePolecat_Server::handleCriticalError(
            AblePolecat_Error::BOOTSTRAP_CONFIG,
            'System configuration may be corrupted. @see ' . self::ELEMENT_VERSION
          );
        }
        $this->Variables[self::ELEMENT_CORE][self::ELEMENT_VERSION][self::ATTRIBUTE_NAME] = 
          $Element->getAttribute(self::ATTRIBUTE_NAME);
        foreach($Element->childNodes as $nodeKey => $Node) {
          if ($Node->nodeName != '#text') {
            $this->Variables[self::ELEMENT_CORE][self::ELEMENT_VERSION][$Node->nodeName] = $Node->nodeValue;
          }
        }
      }
    }
    return $this->Variables[self::ELEMENT_CORE][self::ELEMENT_VERSION];
  }
  
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
   * @return AblePolecat_Conf_Dom or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    //
    // Create conf object
    //
    $Conf = new AblePolecat_Conf_Dom();
    
    //
    // Merge multiple XML conf files into one DOM document
    //
    $confPath = AblePolecat_Server_Paths::getFullPath('conf');
    $Conf->mergePartialXml(
      $confPath . DIRECTORY_SEPARATOR . self::CONF_FILENAME_CORE,
      self::ELEMENT_VERSION, 
      self::ELEMENT_CORE
    );
    $Conf->mergePartialXml(
      $confPath . DIRECTORY_SEPARATOR . self::CONF_FILENAME_HOST,
      self::ELEMENT_DBS, 
      self::ELEMENT_ROOT
    );
    
    return $Conf;
  }
}