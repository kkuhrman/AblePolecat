<?php
/**
 * @file      polecat/core/Dom/Document.php
 * @brief     Encapsulates PHP DOMDocument and exposes interface.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom', 'Node.php')));

interface AblePolecat_Dom_DocumentInterface extends AblePolecat_Dom_NodeInterface {
  // Properties
  // readonly public string $actualEncoding ;
  // readonly public DOMConfiguration $config ;
  // readonly public DOMDocumentType $doctype ;
  // readonly public DOMElement $documentElement ;
  // public string $documentURI ;
  // public string $encoding ;
  // public bool $formatOutput ;
  // readonly public DOMImplementation $implementation ;
  // public bool $preserveWhiteSpace = true ;
  // public bool $recover ;
  // public bool $resolveExternals ;
  // public bool $standalone ;
  // public bool $strictErrorChecking = true ;
  // public bool $substituteEntities ;
  // public bool $validateOnParse = false ;
  // public string $version ;
  // readonly public string $xmlEncoding ;
  // public bool $xmlStandalone ;
  // public string $xmlVersion ;
  /* Methods */
  
  /**
   *  Create Document from a file.
   *
   * Unix style paths with forward slashes can cause significant performance degradation 
   * on Windows systems; be sure to call realpath() in such a case. 
   *
   * @param string $filename 
   * @param int $options
   *
   * @return AblePolecat_Dom_DocumentInterface. 
   */
  public static function createFromFile($filename, $options = 0);
  
  /**
   * Create Document from a string.
   * 
   * @param string $source The HTML string.
   * @param int $options  Additional Libxml parameters.
   *
   * @return AblePolecat_Dom_DocumentInterface. 
   */
  public static function createFromString($source, $options = 0);
  
  /**
   * Creates a new instance of class DOMAttr.
   *
   * @param string $name The name of the attribute.
   *
   * @return DOMAttr or FALSE.
   */
  public function createAttribute($name);
  
  /**
   *  Create new attribute node with an associated namespace
   * 
   * @param string $namespaceURI The URI of the namespace.
   * @param string $qualifiedName The tag name and prefix of the attribute, as prefix:tagname.
   *
   * @return DOMAttr or FALSE.
   */
  public function createAttributeNS($namespaceURI, $qualifiedName);
  
  /**
   *  Create new cdata node.
   *
   * @param  string $data The content of the cdata.
   *
   * @return DOMCDATASection or FALSE.
   */
  public function createCDATASection($data);
  
  /**
   * Create new comment node.
   *
   * @param string $data The content of the comment. 
   * 
   * @return DOMComment or FALSE.
   */
  public function createComment($data);
  
  /**
   * Create new document fragment.
   *
   * @return DOMDocumentFragment or FALSE.
   */
  public function createDocumentFragment();
  
  /**
   * Create new element node.
   * @param string $name The tag name of the element.  
   * @param string $value The value of the element.
   *
   * @return DOMElement or FALSE.
   */
  public function createElement($name, $value = NULL);
  
  /**
   * Create new element node with an associated namespace.
   *
   * @param string $namespaceURI The URI of the namespace. 
   * @param string $qualifiedName The qualified name of the element, as prefix:tagname. 
   * @param string $value The value of the element. 
   *
   * @return DOMElement or FALSE.
   */
  public function createElementNS($namespaceURI, $qualifiedName, $value = NULL);
  
  /**
   * Create new entity reference node.
   *
   * @param string $name
   *
   * @return DOMEntityReference or FALSE.
   */
  public function createEntityReference($name);
  
  /**
   * Creates new PI node.
   *
   * @param string $target The target of the processing instruction. 
   * @param string $data The content of the processing instruction. 
   *
   * @return DOMProcessingInstruction or FALSE.
   */
  public function createProcessingInstruction($target, $data = NULL);
  
  /**
   * Create new text node.
   *
   * @param string $content.
   *
   * @return DOMText or FALSE.
   */
  public function createTextNode($content);
  
  /**
   * Searches for an element with a certain id.
   *
   * @param string $elementId The unique id value for an element. 
   *
   * @return DOMElement or NULL.
   */
  public function getElementById($elementId);
  
  /**
   * Searches for all elements with given local tag name.
   *
   * The special value * matches all tags. 
   *
   * @param string $name The local name (without namespace) of the tag to match on. 
   *
   * @return DOMNodeList.
   */
  public function getElementsByTagName($name);
  
  /**
   * Searches for all elements with given tag name in specified namespace.
   *
   * @param string $namespaceURI he namespace URI of the elements to match on.
   * @param string $localName The local name of the elements to match on.
   *
   * @return DOMNodeList.
   */
  public function getElementsByTagNameNS($namespaceURI, $localName);
  
  /**
   *  Import node into current document.
   *
   * @param DOMNode $importedNode The node to import.
   * @param bool $recursive If TRUE, recursively import the sub-tree.
   *
   * @return DOMNode or FALSE.
   */
  public function importNode(DOMNode $importedNode, $recursive = TRUE);
  
  /**
   * Normalizes the document.
   */
  public function normalizeDocument();
  
  /**
   * Performs relaxNG validation on the document.
   * 
   * @param string $filename The RNG file.
   *
   * @return Returns TRUE on success or FALSE on failure.
   */
  public function relaxNGValidate($filename);
  
  /**
   * Performs relaxNG validation on the document.
   *
   * @param string $source A string containing the RNG schema.
   *
   * @return Returns TRUE on success or FALSE on failure.
   */
  public function relaxNGValidateSource($source);
  
  /**
   *  Save the DOM tree into a file.
   *
   * @param string $filename The path to the saved XML document.
   * @param int $options Additional Options. Currently only LIBXML_NOEMPTYTAG is supported. 
   *
   * @return Returns the number of bytes written or FALSE if an error occurred.
   */
  public function saveAsFile($filename, $options = 0);
  
  /**
   * Save the internal DOM tree back as a string.
   *
   * @param DOMNode $node
   * @param int $options
   * 
   * @return mixed Returns XML as string, or FALSE if an error occurred.
   */
  public function saveAsString(DOMNode $node, $options = 0);
  
  /**
   *  Validates a document based on a schema.
   *
   * Currently the only supported value is LIBXML_SCHEMA_CREATE.
   *
   * @param string $filename The path to the schema.
   * @param int $flags A bitmask of Libxml schema validation flags.
   *
   * @return Returns TRUE on success or FALSE on failure.
   */
  public function schemaValidate($filename, $flags = 0);
  
  /**
   * Validates a document based on a schema.
   * 
   * @param string $source A string containing the schema.
   * @param int $flags A bitmask of Libxml schema validation flags.
   *
   * @return bool Returns TRUE on success or FALSE on failure. 
   */
  public function schemaValidateSource($source, $flags = 0);
  
  /**
   * Validates the document based on its DTD.
   *
   * If no DTD is attached to the document, this method will return FALSE.
   *
   * @return bool Returns TRUE on success or FALSE on failure.
   */
  public function validate();
  
  /**
   * Substitutes XIncludes in a DOMDocument Object.
   * 
   * @param int $options libxml parameters.
   *
   * @return int Returns the number of XIncludes in the document, 
   * -1 if some processing failed, or FALSE if there were no substitutions. 
   */
  public function xinclude($options =0);
  
  // public bool registerNodeClass ( string $baseclass , string $extendedclass )
}

abstract class AblePolecat_Dom_DocumentAbstract implements AblePolecat_Dom_DocumentInterface {
  
  /**
   * @var DOMDocument.
   */
  protected $Document;
  
  /********************************************************************************
   * Implementation of AblePolecat_Data_PrimitiveInterface.
   ********************************************************************************/
  
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL) {
    return $this->Document;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Dom_DocumentInterface.
   ********************************************************************************/
  
  /**
   * Creates a new instance of class DOMAttr.
   *
   * @param string $name The name of the attribute.
   *
   * @return DOMAttr or FALSE.
   */
  public function createAttribute($name) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createAttribute($name);
    }
    return $returnValue;
  }
  
  /**
   *  Create new attribute node with an associated namespace
   * 
   * @param string $namespaceURI The URI of the namespace.
   * @param string $qualifiedName The tag name and prefix of the attribute, as prefix:tagname.
   *
   * @return DOMAttr or FALSE.
   */
  public function createAttributeNS($namespaceURI, $qualifiedName) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createAttributeNS($namespaceURI, $qualifiedName);
    }
    return $returnValue;
  }
  
  /**
   *  Create new cdata node.
   *
   * @param  string $data The content of the cdata.
   *
   * @return DOMCDATASection or FALSE.
   */
  public function createCDATASection($data) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createCDATASection($data);
    }
    return $returnValue;
  }
  
  /**
   * Create new comment node.
   *
   * @param string $data The content of the comment. 
   * 
   * @return DOMComment or FALSE.
   */
  public function createComment($data) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createComment($data);
    }
    return $returnValue;
  }
  
  /**
   * Create new document fragment.
   *
   * @return DOMDocumentFragment or FALSE.
   */
  public function createDocumentFragment() {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createDocumentFragment();
    }
    return $returnValue;
  }
  
  /**
   * Create new element node.
   * @param string $name The tag name of the element.  
   * @param string $value The value of the element.
   *
   * @return DOMElement or FALSE.
   */
  public function createElement($name, $value = NULL) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createElement($name, $value);
    }
    return $returnValue;
  }
  
  /**
   * Create new element node with an associated namespace.
   *
   * @param string $namespaceURI The URI of the namespace. 
   * @param string $qualifiedName The qualified name of the element, as prefix:tagname. 
   * @param string $value The value of the element. 
   *
   * @return DOMElement or FALSE.
   */
  public function createElementNS($namespaceURI, $qualifiedName, $value = NULL) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createElementNS($namespaceURI, $qualifiedName, $value);
    }
    return $returnValue;
  }
  
  /**
   * Create new entity reference node.
   *
   * @param string $name
   *
   * @return DOMEntityReference or FALSE.
   */
  public function createEntityReference($name) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createEntityReference($name);
    }
    return $returnValue;
  }
  
  /**
   * Creates new PI node.
   *
   * @param string $target The target of the processing instruction. 
   * @param string $data The content of the processing instruction. 
   *
   * @return DOMProcessingInstruction or FALSE.
   */
  public function createProcessingInstruction($target, $data = NULL) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createProcessingInstruction($target, $data);
    }
    return $returnValue;
  }
  
  /**
   * Create new text node.
   *
   * @param string $content.
   *
   * @return DOMText or FALSE.
   */
  public function createTextNode($content) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->createTextNode($content);
    }
    return $returnValue;
  }
  
  /**
   * Searches for an element with a certain id.
   *
   * @param string $elementId The unique id value for an element. 
   *
   * @return DOMElement or NULL.
   */
  public function getElementById($elementId) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->getElementById($elementId);
    }
    return $returnValue;
  }
  
  /**
   * Searches for all elements with given local tag name.
   *
   * The special value * matches all tags. 
   *
   * @param string $name The local name (without namespace) of the tag to match on. 
   *
   * @return DOMNodeList.
   */
  public function getElementsByTagName($name) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->getElementsByTagName($name);
    }
    return $returnValue;
  }
  
  /**
   * Searches for all elements with given tag name in specified namespace.
   *
   * @param string $namespaceURI he namespace URI of the elements to match on.
   * @param string $localName The local name of the elements to match on.
   *
   * @return DOMNodeList.
   */
  public function getElementsByTagNameNS($namespaceURI, $localName) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->getElementsByTagNameNS($namespaceURI, $localName);
    }
    return $returnValue;
  }
  
  /**
   *  Import node into current document.
   *
   * @param DOMNode $importedNode The node to import.
   * @param bool $recursive If TRUE, recursively import the sub-tree.
   *
   * @return DOMNode or FALSE.
   */
  public function importNode(DOMNode $importedNode, $recursive = TRUE) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->importNode($importedNode, $recursive);
    }
    return $returnValue;
  }
  
  /**
   * Normalizes the document.
   */
  public function normalizeDocument() {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->normalizeDocument();
    }
    return $returnValue;
  }
  
  /**
   * Performs relaxNG validation on the document.
   * 
   * @param string $filename The RNG file.
   *
   * @return Returns TRUE on success or FALSE on failure.
   */
  public function relaxNGValidate($filename) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->relaxNGValidate($filename);
    }
    return $returnValue;
  }
  
  /**
   * Performs relaxNG validation on the document.
   *
   * @param string $source A string containing the RNG schema.
   *
   * @return Returns TRUE on success or FALSE on failure.
   */
  public function relaxNGValidateSource($source) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->relaxNGValidateSource($source);
    }
    return $returnValue;
  }
    
  /**
   *  Validates a document based on a schema.
   *
   * Currently the only supported value is LIBXML_SCHEMA_CREATE.
   *
   * @param string $filename The path to the schema.
   * @param int $flags A bitmask of Libxml schema validation flags.
   *
   * @return Returns TRUE on success or FALSE on failure.
   */
  public function schemaValidate($filename, $flags = 0) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->schemaValidate($filename, $flags);
    }
    return $returnValue;
  }
  
  /**
   * Validates a document based on a schema.
   * 
   * @param string $source A string containing the schema.
   * @param int $flags A bitmask of Libxml schema validation flags.
   *
   * @return bool Returns TRUE on success or FALSE on failure. 
   */
  public function schemaValidateSource($source, $flags = 0) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->schemaValidateSource($source, $flags);
    }
    return $returnValue;
  }
  
  /**
   * Validates the document based on its DTD.
   *
   * If no DTD is attached to the document, this method will return FALSE.
   *
   * @return bool Returns TRUE on success or FALSE on failure.
   */
  public function validate() {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->validate();
    }
    return $returnValue;
  }
  
  /**
   * Substitutes XIncludes in a DOMDocument Object.
   * 
   * @param int $options libxml parameters.
   *
   * @return int Returns the number of XIncludes in the document, 
   * -1 if some processing failed, or FALSE if there were no substitutions. 
   */
  public function xinclude($options =0) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->xinclude($options);
    }
    return $returnValue;
  }
}