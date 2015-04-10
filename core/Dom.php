<?php
/**
 * @file      polecat/core/Dom.php
 * @brief     A helper class encapsulates common DOM/XML creation/manipulation routines.
 *
 * Able Polecat uses DOM settings which often differ from the PHP defaults and
 * this class helps with the tedium of overriding these. It also provides 
 * helper functions for common DOM-related tasks, such as creating a DOM 
 * sub-tree from a markup fragment in a file and then merging it into a DOM
 * document.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Dom.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Data.php');

class AblePolecat_Dom {
  
  /**
   * @todo: XHTML 1.1 hard-coded here; should extend to allow for other doc types.
   */
  const XHTML_1_1_QUALIFIED_NAME  = 'html';
  const XHTML_1_1_PUBLIC_ID       = "-//W3C//DTD XHTML 1.1//EN";
  const XHTML_1_1_SYSTEM_ID       = "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd";
  const XHTML_1_1_NAMESPACE_URI   = "http://www.w3.org/1999/xhtml";
  
  const DOM_DIRECTIVE_KEY_OP              = 'op';
  const DOM_DIRECTIVE_KEY_FRAGMENT_PARENT = 'fragmentParent';
  const DOM_DIRECTIVE_KEY_DOCUMENT_PARENT = 'documentParent';
  const DOM_DIRECTIVE_KEY_REPLACE_NODE    = 'replaceNode';
  const DOM_DIRECTIVE_KEY_RECURSIVE       = 'recursive';
  const DOM_FRAGMENT_OP_APPEND  = 'APPEND'; 
  const DOM_FRAGMENT_OP_INSERT  = 'INSERT';
  const DOM_FRAGMENT_OP_REPLACE = 'REPLACE';
  
  /**
   * Append child element to given parent or one identified by tag or id.
   *
   * @param DOMNode $child Child node.
   * @param DOMDocument $document DOM Document.
   * @param mixed $parent Parent node | Array['id' => Array[ID attribute name => ID value]] | ['tag' => Array[tag name => DOMNodeList index]]
   * @param bool $recursive IF TRUE, recursively import the subtree under $child. 
   *
   * @return DOMNode The appended child node.
   * @see expressIdAttribute()
   * @see expressNodeListTag()
   */
  public static function appendChildToParent(DOMNode $child, DOMDocument $document, $parent = NULL, $recursive = TRUE) {
    
    $Node = NULL;
    
    //
    // Import child node into parent document.
    //
    $importChild = $document->importNode($child, $recursive);
      
    if (isset($parent)) {
      if (is_a($parent, 'DOMNode')) {
        //
        // Append child to given parent node
        //
        $Node = $parent->appendChild($importChild);
      }
      else if (is_array($parent)) {
        //
        // Find given parent node in document
        //
        $ParentElement = NULL;
        if (isset($parent['id'])) {
          isset($parent['id']['attributeValue']) ? $attributeValue = $parent['id']['attributeValue'] : $attributeValue = NULL;
          isset($attributeValue) ? $ParentElement = self::getElementById($document, $attributeValue) : NULL;
        }
        else if (isset($parent['tag'])) {
          isset($parent['id']['tagName']) ? $tagName = $parent['id']['tagName'] : $tagName = NULL;
          isset($parent['id']['listIndex']) ? $listIndex = $parent['id']['listIndex'] : $listIndex = 0;
          isset($tagName) ? $ParentElement = self::getElementsByTagName($document, $tagName, $listIndex) : NULL;
        }
        if (isset($ParentElement)) {
          $Node = $ParentElement->appendChild($importChild);
        }
      }
    }
    else {
      //
      // No parent node given, append to document element.
      //
      $Node = $document->documentElement->appendChild($importChild);
    }
    if (!isset($Node)) {
      throw new AblePolecat_Dom_Exception(sprintf("Failed to merge element given by [%s] with document body.", $child->nodeName));
    }
    return $Node;
  }
  
  /**
   * Append child element to given parent or one identified by tag or id.
   *
   * @param AblePolecat_Data_PrimitiveInterface $data Child node data.
   * @param DOMDocument $document DOM Document.
   * @param mixed $parent Parent node | Array['id' => Array[ID attribute name => ID value]] | ['tag' => Array[tag name => DOMNodeList index]]
   * @param bool $recursive IF TRUE, recursively import the subtree under $child. 
   *
   * @return DOMNode The appended child node.
   * @see expressIdAttribute()
   * @see expressNodeListTag()
   */
  public static function appendData(AblePolecat_Data_PrimitiveInterface $data, DOMDocument $document, $parent = NULL, $recursive = TRUE) {
    // DOMNode $child;
    return self::appendChildToParent($child, $document, $parent, $recursive);
  }
  
  /**
   * Create XML DOM Document.
   * 
   * @param string $rootElementName Name of top-level document element.
   * @param string $namespaceURI  The namespace URI of the document element to create.
   * @param string $xmlVersion
   * @param string $xmlEncoding
   *
   * @return DOMDocument
   */
  public static function createXmlDocument(
    $rootElementName = 'root',
    $namespaceURI = NULL,
    $xmlVersion = '1.0',
    $xmlEncoding = 'UTF-8'
  ) {
    $DOMImpl = new DOMImplementation();
    $Document = $DOMImpl->createDocument($namespaceURI, $rootElementName);
    $Document->xmlVersion = $xmlVersion;
    $Document->xmlStandalone = TRUE;
    // $Document->xmlEncoding = $xmlEncoding;
    return $Document;
  }
  
  /**
   * Create XHTML DOM Document
   *
   * @param string $namespaceURI  The namespace URI of the document element to create.
   * @param string $qualifiedName The qualified name of the document element to create.
   * @param string $publicId      The external subset public identifier.
   * @param string $systemId      The external subset system identifier.
   * @param array $documentProperties Array[DOMDocument property name => DOMDocument property value]
   *
   * @return DOMDocument
   */
  public static function createDocument(
    $namespaceURI = NULL, 
    $qualifiedName = 'html', 
    $publicId = NULL, 
    $systemId = NULL, 
    $documentProperties = NULL
  ) {
    
    //
    // Create doc type
    //
    $DOMImpl = new DOMImplementation();
    $DocType = $DOMImpl->createDocumentType(
      $qualifiedName,
      $publicId,
      $systemId
    );
    
    //
    // Create document
    //
    $Document = $DOMImpl->createDocument(
      $namespaceURI, 
      $qualifiedName,
      $DocType
    );
    
    //
    // Project defaults
    //
    $Document->formatOutput = TRUE;
    $Document->strictErrorChecking = FALSE;
    $Document->preserveWhiteSpace = FALSE;
    $Document->validateOnParse = FALSE;
    
    //
    // Allow override of defaults
    //
    if (isset($documentProperties) && is_array($documentProperties)) {
      foreach($documentProperties as $propertyName => $propertyValue) {
        if (property_exists($Document, $propertyName)) {
          $Document->$propertyName = $propertyValue;
        }
      }
    }
    
    return $Document;
  }
  
  /**
   * Create XHTML DOM Document from template stored in a file.
   *
   * @param string $fullPath  Full path to template file.
   * @param string $qualifiedName The qualified name of the document element to create.
   * @param string $publicId      The external subset public identifier.
   * @param string $systemId      The external subset system identifier.
   * @param array $documentProperties Array[DOMDocument property name => DOMDocument property value]
   *
   * @return DOMDocument
   */
  public static function createDocumentFromTemplate($fullPath) {
    
    $Document = NULL;
    $realPath = realpath($fullPath);
    if (file_exists($realPath)) {
      $Document = AblePolecat_Dom::createDocument(
        AblePolecat_Dom::XHTML_1_1_NAMESPACE_URI,
        AblePolecat_Dom::XHTML_1_1_QUALIFIED_NAME,
        AblePolecat_Dom::XHTML_1_1_PUBLIC_ID,
        AblePolecat_Dom::XHTML_1_1_SYSTEM_ID
      );
      $Document->loadHTMLFile($realPath);
    }
    return $Document;
  }
  
  /**
   * Create DOM element by inserting data from object into template string.
   *
   * @param DOMDocument $Document Parent DOM Document .
   * @param DOMElement $parentElement Parent DOM Element (container for repeatable elements).
   * @param AblePolecat_Data_StructureInterface $Data Data to insert into template.
   * @param string $elementTemplateStr Text template of repeatable element.
   *
   * @return DOMElement The newly created element.
   * @see AblePolecat_Dom::removeRepeatableElementTemplate
   */
  public static function createRepeatableElementFromTemplate(
    DOMDocument $Document,
    DOMElement $parentElement,
    AblePolecat_Data_StructureInterface $Data,
    $elementTemplateStr
  ) {
    //
    // @todo: this cannot remain as a long-term solution to the problem of warnings and errors
    // triggered by loadHTML() (@see getDocumentElementFromString()).
    // All it does is log errors vs. handle them.
    //
    libxml_use_internal_errors(TRUE);
    
    //
    // Iterate through list, creating element text for each item by string substitution.
    // Notation is {!property_name} where the entire string will be replaced with the
    // value corresponding to the property given by property_name.
    //
          
    $substituteMarkers = array();
    $substituteValues = array();
    $Property = $Data->getFirstProperty();
    while($Property) {
      $substituteMarkers[] = sprintf("{!%s}", $Data->getPropertyKey());
      $substituteValues[] = $Property->__toString();
      $Property = $Data->getNextProperty();
    }
    $listItemElementStr = str_replace($substituteMarkers, $substituteValues, $elementTemplateStr);
    $Element = @AblePolecat_Dom::getDocumentElementFromString($listItemElementStr);
    $Element = AblePolecat_Dom::appendChildToParent(
      $Element, 
      $Document,
      $parentElement
    );
    
    // 
    // @see call to at begin of function
    //
    foreach (libxml_get_errors() as $error) {
      // 
      // @todo: handle errors here
      //
      $errorMsg = sprintf("libxml error in AblePolecat_Dom: %s", $error->message);
      isset($error->column) ? $errorMsg .= sprintf(" (column %d)", $error->column) : NULL;
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $errorMsg);
    }

    libxml_clear_errors();
    
    return $Element;
  }
  
  /**
   * Helper function - express array as element attributes.
   *
   * @param Array $attributes
   * @param int $pad  STR_PAD_RIGHT | STR_PAD_LEFT | STR_PAD_BOTH
   *
   * @return string.
   */
  public static function expressElementAttributes($attributes, $pad = STR_PAD_LEFT) {
    
    $attributesSyntax = '';
    if (isset($attributes) && is_array($attributes)) {
      $attributesSyntax = array();
      foreach($attributes as $attributeName => $attributeValue) {
        $attributesSyntax[] = sprintf("%s=\"%s\"", $attributeName, $attributeValue);
      }
      $attributesSyntax = implode(' ', $attributesSyntax);
    }
    if ($attributesSyntax != '') {
      //
      // pad to separate from other markup
      //
      $attributesSyntax = str_pad($attributesSyntax, 1 + strlen($attributesSyntax), ' ', $pad);
    }
    return $attributesSyntax;
  }
  
  /**
   * Express an id attribute as an Array.
   *
   * @param string $attributeName The name of the ID attribute.
   * @param string $attributeValue The value of the ID attribute.
   *
   * @return Array ['id' => Array[ID attribute name => ID value]]
   */
  public static function expressIdAttribute($attributeName, $attributeValue) {
    return array('id' => array('attributeName' => $attributeName, 'attributeValue' => $attributeValue));
  }
  
  /**
   * Express an item in a DOMNodeList as an Array.
   *
   * @param string $tagName Node name or element tag name.
   * @param int    $listIndex Index of item in list.
   *
   * @return Array ['tag' => Array[tag name => DOMNodeList index]]
   */
  public static function expressNodeListTag($tagName, $listIndex = 0) {
    return array('tag' => array('tagName' => $tagName, 'listIndex' => $listIndex));
  }
  
  /**
   * Helper function - removes potentially harmful or forbidden characters from response content.
   *
   * @param string $unfiltered.
   *
   * @return string Filtered output.
   */
  public static function filterText($unfiltered) {
    // mb_convert_encoding($text,'ISO-8859-15','utf-8');
    $filtered = htmlspecialchars($unfiltered);
    return $filtered;
  }
  
  /**
   * Find requested DOMNode in given document.
   *
   * @param DOMDocument $document DOM Document.
   * @param mixed $node Parent node | Array['id' => Array[ID attribute name => ID value]] | ['tag' => Array[tag name => DOMNodeList index]]
   *
   * @return DOMNode The requested node if found otherwise NULL.
   */
  public static function findDomNode(DOMDocument $document, $node) {
    
    $Node = NULL;
    
    if (is_array($node)) {
      //
      // Find given node in document
      //
      if (isset($node['id'])) {
        isset($node['id']['attributeValue']) ? $attributeValue = $node['id']['attributeValue'] : $attributeValue = NULL;
        isset($attributeValue) ? $Node = self::getElementById($document, $attributeValue) : NULL;
      }
      else if (isset($node['tag'])) {
        isset($node['id']['tagName']) ? $tagName = $node['id']['tagName'] : $tagName = NULL;
        isset($node['id']['listIndex']) ? $listIndex = $node['id']['listIndex'] : $listIndex = 0;
        isset($tagName) ? $Node = self::getElementsByTagName($document, $tagName, $listIndex) : NULL;
      }
    }
    return $Node;
  }
  
  /**
   * Load markup into DOM Document and detach document element.
   *
   * @param string $fileName Name of file containing markup.
   *
   * @return DOMElement Document element.
   */
  public static function getDocumentElementFromFile($fileName) {
    
    $Element = NULL;
    
    if (file_exists($fileName)) {
      //
      // Create a document from the template fragment
      //
      $Document = self::createDocument();
      @$Document->loadHTMLFile($fileName);
      // $DocumentElement = $Document->documentElement;
      
      //
      // Break off the fragment from the newly created document
      //
      $BodyElement = self::getElementsByTagName($Document, 'body', 0);
      $Element = $BodyElement->firstChild;
    }
    return $Element;
  }
  
  /**
   * Load markup into DOM Document and detach document element.
   *
   * @param string $markup String containing markup.
   * @param string $parent 'html' | 'head' | 'body'
   *
   * @return DOMElement Document element.
   */
  public static function getDocumentElementFromString($markup, $parent = 'body') {
    
    $Document = self::createDocument();
    @$Document->loadHTML($markup);
    $Elements = self::getElementsByTagName($Document, $parent);
    $parentElement = $Elements->item(0);
    isset($parentElement) ? $Element = $parentElement->firstChild : $Element = NULL;
    return $Element;
  }
  
  /**
   * Helper function to overcome performance issues relating to PHP/DOM validation of XHTML.
   *
   * @param DOMDocument $Document Document to search.
   * @param string $elementId The unique id value for an element.
   *
   * @return DOMElement or NULL if the element is not found. 
   */
  public static function getElementById(DOMDocument $Document, $elementId) {
    
    $Element = NULL;
    
    $xpath = new DOMXPath($Document);
    $NodeList = $xpath->query("//*[@id='$elementId']");
    if (isset($NodeList)) {
      $Element = $NodeList->item(0);
    }
    return $Element;
  }
  
  /**
   * Helper function to cheat when only one element is expected (as in a doc fragment).
   *
   * @param DOMDocument $Document Document to search.
   * @param string $tagName The local name (without namespace) of the tag to match on.
   * @param int $ordinal The index of the item in the resultant DOMNodeList.
   *
   * @return mixed DOMNodeList if $ordinal is NULL, otherwise DOMElement or NULL.
   */
  public static function getElementsByTagName(DOMDocument $Document, $tagName, $ordinal = NULL) {
    
    $Element = NULL;
    
    $NodeList = $Document->getElementsByTagName($tagName);
    if (isset($ordinal) && isset($NodeList)) {
      $Element = $NodeList->item($ordinal);
    }
    else {
      $Element = $NodeList;
    }
    return $Element;
  }
  
  /**
   * Insert XML or XHTML fragment into DOM Document.
   *
   * Able Polecat best practice is to store theme files (templates, scripts, 
   * style sheets, etc) in the ./[project root]/usr/theme/[THEME_NAME].
   * Fragments, which will be shared by all resources should be stored in
   * ./[project root dir]/usr/theme/[THEME_NAME]/template/default/[fragment file].
   * Fragments, which are specific to a named resource should be stored in
   * ./[project root dir]/usr/theme/[THEME_NAME]/template/[RESOURCE_NAME]/[fragment file].
   *
   * @param DOMDocument $Document Document into which fragment will be inserted.
   * @param mixed $templateSearchPaths String or array (see note above about fragment paths).
   * @param Array $domDirectives Associative array (see note above).
   *
   * @todo: probably pass class registration as parameter so we know what to update with
   * file modified time
   *
   * @return DOMNode imported DOM node.
   * @see appendChildToParent().
   */
  public static function loadTemplateFragment(
    DOMDocument $Document,
    $templateSearchPaths,
    $domDirectives = NULL
    // $fragmentParent = self::ELEMENT_BODY,
    // $parent = NULL,
    // $recursive = TRUE
  ) {
    
    $fragmentNode = NULL;
    $templateFullPath = NULL;
    $templateBodyStr = NULL;
    $fileModifiedTime = 0;
    
    $domDirectives = self::validateDomDirectives($domDirectives);
    if ($domDirectives) {
      //
      // First conditional is compromise between allowing mixed parameter vs.
      // not wanting to repeat file_exists() conditional code below.
      //
      if (is_scalar($templateSearchPaths) && is_string($templateSearchPaths)) {
        $templateSearchPaths = array($templateSearchPaths);
      }    
      if (is_array($templateSearchPaths)) {
        foreach($templateSearchPaths as $key => $path) {
          if (file_exists($path)) {
            $templateFullPath = $path;
            $templateBodyStr = file_get_contents($templateFullPath);
            $fileModifiedTime = filemtime($templateFullPath);
            break;
          }
        }
        
        //
        // @todo: is fragment modified time more recent that parent document modified time?
        // IOW - do we need to re-cache this page?
        //
        
        //
        // Insert template body into document.
        //
        if (isset($templateBodyStr)) {
          $recursive = $domDirectives[self::DOM_DIRECTIVE_KEY_RECURSIVE];
          
          //
          // Create temporary DOMDocument from fragment.
          //
          $fragmentParent = 'body';
          if (isset($domDirectives[self::DOM_DIRECTIVE_KEY_FRAGMENT_PARENT])) {
            $fragmentParent = $domDirectives[self::DOM_DIRECTIVE_KEY_FRAGMENT_PARENT];
          }
          $fragmentNode = @AblePolecat_Dom::getDocumentElementFromString($templateBodyStr, $fragmentParent);
          
          //
          // Locate fragment parent element and extract fragment.
          //
          $documentParent = NULL;
          if (isset($domDirectives[self::DOM_DIRECTIVE_KEY_DOCUMENT_PARENT])) {
            $documentParent = $domDirectives[self::DOM_DIRECTIVE_KEY_DOCUMENT_PARENT];
          }
          switch ($domDirectives[self::DOM_DIRECTIVE_KEY_OP]) {
            default:
            case self::DOM_FRAGMENT_OP_APPEND:
              $fragmentNode = self::appendChildToParent($fragmentNode, $Document, $documentParent);
              break;
            case self::DOM_FRAGMENT_OP_INSERT:
              break;
            case self::DOM_FRAGMENT_OP_REPLACE:
              if (isset($domDirectives[self::DOM_DIRECTIVE_KEY_REPLACE_NODE])) {
                $oldNode = $domDirectives[self::DOM_DIRECTIVE_KEY_REPLACE_NODE];
                self::replaceDomNode($Document, $fragmentNode, $oldNode, $recursive);
              }
              break;
          }
        }
        else {
          foreach($templateSearchPaths as $key => $path) {
            $message = sprintf("No valid template fragment file was found at %s.", $path);
            AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
          }
        }
      }
      else {
        $message = sprintf("%s requires string or array containing template path(s). %s passed.",
          __METHOD__,
          AblePolecat_Data::getDataTypeName($templateSearchPaths)
        );
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
      }
    }
    
    return $fragmentNode;
  }
  
  /**
   * Replace one DOM node with another.
   *
   * @param DOMDocument $Document DOM Document, which will be manipulated.
   * @param mixed $oldNode See notes.
   * @param DOMNode $newNode See notes.
   * @param bool $recursive IF TRUE, recursively import the sub-tree under $newNode. 
   * 
   * @return mixed DOMNode ($oldNode) if successful, otherwise FALSE.
   */
  public static function replaceDomNode(
    DOMDocument $Document,
    DOMNode $newNode,
    $oldNode,
    $recursive = TRUE
  ) {
    
    $returnVal = FALSE;
    //
    // Make sure oldNode is properly typecast.
    //
    if (is_array($oldNode)) {
      $oldNode = self::findDomNode($Document, $oldNode);
    }
    else if (!is_a($oldNode, 'DOMNode')) {
      $message = sprintf("%s parameter 3 requires DOMNode or array. %s passed.",
        __METHOD__,
        AblePolecat_Data::getDataTypeName($oldNode)
      );
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
    }
    if (isset($oldNode)) {
      $parentNode = $oldNode->parentNode;
      if (isset($parentNode)) {
        $newNode = $Document->importNode($newNode, $recursive);
        $returnVal = $parentNode->replaceChild($newNode, $oldNode);
      }
    }
    return $returnVal;
  }
  
  /**
   * Helper function - find element given by id and remove from document if found.
   *
   * @param DOMDocument $Document DOM Document, which will be manipulated.
   * @param string $elementId Value of parent element id attribute.
   *
   * @return DOMNode Old element if found otherwise NULL.
   */
  public static function removeElement(DOMDocument $Document, $elementId) {
    $Element = AblePolecat_Dom::getElementById($Document, $elementId);
    $parentElement = $Element->parentNode;
    $Element = $parentElement->removeChild($Element);
    return $Element;
  }
  
  /**
   * Helper function - remove the first child of the node with given id.
   *
   * This function is used to allow designer to create a template for repeatable
   * elements such as list items, table rows, etc. The mark up for the element is
   * included in the template at design time. At runtime, this function is called
   * in the response object to strip the design element and use it to create the 
   * data elements in its place.
   *
   * @param DOMDocument $Document DOM Document, which will be manipulated.
   * @param string $parentElementId Value of parent element id attribute.
   * 
   * @return string The removed node as text.
   */
  public static function removeRepeatableElementTemplate(DOMDocument $Document, $parentElementId) {
    
    $templateElementStr = NULL;
    
    //
    // Create a temporary DOM document and append element.
    //
    $parentElement = AblePolecat_Dom::getElementById($Document, $parentElementId);
    if (isset($parentElement)) {
      //
      // Convert parent element to text.
      //
      $templateElementStr = $parentElement->C14N();
      
      //
      // Remove encoded/unencoded, carriage returns and other junk.
      //
      $templateElementStr = trim(str_replace(array('&#xD;'), '', $templateElementStr));
      
      //
      // Get first child of type DOMElement
      //
      $childElement = NULL;
      if ($parentElement->hasChildNodes()) {
        //
        // Pass over DOMText and other white space nodes...
        //
        foreach($parentElement->childNodes as $key => $Node) {
          if (is_a($Node, 'DOMElement')) {
            $childElement = $Node;
          }
        }
      }
      
      if (!isset($childElement)) {
        throw new AblePolecat_Dom_Exception("Could not locate DOM element with id=$parentElementId");
      }
      
      //
      // Remove the repeatable element(s) from the parent.
      //
      $childElement = $parentElement->removeChild($childElement);
      
      //
      // Convert parent (without repeatable child element) to text.
      //
      $parentElementStr = $parentElement->C14N();
      
      //
      // Strip parent element tags from HTML text to produce repeatable element 'template'
      //
      $pos = strpos($parentElementStr, '>');
      $templateElementStr = trim(substr($templateElementStr, 1 + $pos));
      $pos = strrpos($templateElementStr, '<');
      $templateElementStr = trim(substr($templateElementStr, 0, -1 * (strlen($templateElementStr) - $pos)));
    }
    
    return $templateElementStr;
  }
  
  /**
   * Check parameters passed to certain DOM manipulation functions (e.g. loadTemplateFragment).
   *
   * Parameter $domDirectives is an optional associative array with directive(s)
   * on how to place fragment in DOM. If no array is passed, or a value is not given for
   * one of the keys below, defaults are used.
   * op             - How to place fragment in target DOM document.
   * fragmentParent - Where to find fragment in temporary DOM document loaded from file.
   * documentParent - Where to place fragment in target DOM document.
   * recursive      - TRUE = recursively import the fragment sub-tree; 
   *                  FALSE = import only top-level element of fragment
   * Valid DOM directive values
   * op     
   *  - String APPEND (default) | INSERT | REPLACE
   *  - NULL (defaults to APPEND)
   * fragmentParent 
   *  - String html | head | body
   *  - Array['tag' => Array[tag name => DOMNodeList index]] (points to DOMNode) 
   *  - NULL (defaults to 'body')
   * documentParent
   *  - DOMNode |
   *  - Array['id' => Array[ID attribute name => ID value]] (points to DOMNode) |
   *  - Array['tag' => Array[tag name => DOMNodeList index]] (points to DOMNode) 
   *  - NULL (defaults to top-level DOMNode (first child) in Document)
   * recursive
   *  - Boolean
   *  - NULL (defaults to TRUE - recursively import entire fragment)
   *
   * @param Array $domDirectives Associative array (see note above).
   *
   * @return mixed Valid $domDirectives array or FALSE.
   * @see loadTemplateFragment().
   */
  public static function validateDomDirectives(
    $domDirectives = NULL
  ) {
    
    $domDirectivesChecked = FALSE;
    
    //
    // If parameter is NULL, pass
    //
    if (!isset($domDirectives)) {
      $domDirectivesChecked = array(
        self::DOM_DIRECTIVE_KEY_OP => self::DOM_FRAGMENT_OP_APPEND,
        self::DOM_DIRECTIVE_KEY_RECURSIVE => TRUE,
      );
    }
    else {
      if (is_array($domDirectives)) {
        $domDirectivesChecked = array();
        //
        // Check parameters.
        //
        foreach($domDirectives as $directiveName => $directiveValue) {
          switch($directiveName) {
            default:
              $message = sprintf("Invalid DOM directive parameter %s", $directiveName);
              AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
              break;
            case self::DOM_DIRECTIVE_KEY_OP:
              if (is_string($directiveValue)) {
                switch ($directiveValue) {
                  default:
                    $message = sprintf("Invalid value for DOM directive parameter %s: %s", $directiveName, $directiveValue);
                    AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
                    break;
                  case self::DOM_FRAGMENT_OP_APPEND:
                  case self::DOM_FRAGMENT_OP_INSERT:
                  case self::DOM_FRAGMENT_OP_REPLACE:
                    $domDirectivesChecked[self::DOM_DIRECTIVE_KEY_OP] = $directiveValue;
                    if (($directiveName == self::DOM_FRAGMENT_OP_REPLACE) && !isset($domDirectives[self::DOM_DIRECTIVE_KEY_REPLACE_NODE])) {
                      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, 'DOM directive replace node requires that a target node be specified.');
                    }
                    break;
                }
              }
              else {
                $message = sprintf("Invalid type for DOM directive parameter %s: String expected, %s passed.", 
                  $directiveName,
                  AblePolecat_Data::getDataTypeName($directiveValue)
                );
                AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
              }
              break;
            case self::DOM_DIRECTIVE_KEY_FRAGMENT_PARENT:
              if (is_string($directiveValue)) {
                $domDirectivesChecked[$directiveName] = $directiveValue;
              }
              else if (is_array($directiveValue)) {
                isset($directiveValue['id']['tagName']) ? $domDirectivesChecked[$directiveName] = $directiveValue['id']['tagName'] : NULL;
              }
              else {
                $message = sprintf("Invalid type for DOM directive parameter %s: Array or string expected, %s passed.", 
                  $directiveName,
                  AblePolecat_Data::getDataTypeName($directiveValue)
                );
                AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
              }
              break;
            case self::DOM_DIRECTIVE_KEY_DOCUMENT_PARENT:
            case self::DOM_DIRECTIVE_KEY_REPLACE_NODE:
              if (is_array($directiveValue) || (is_object($directiveValue) && is_a($directiveValue, 'DOMNode'))) {
                $domDirectivesChecked[$directiveName] = $directiveValue;
              }
              else {
                $message = sprintf("Invalid type for DOM directive parameter %s: Array or DOMNode expected, %s passed.", 
                  $directiveName,
                  AblePolecat_Data::getDataTypeName($directiveValue)
                );
                AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
              }
              break;
            case self::DOM_DIRECTIVE_KEY_RECURSIVE:
              if (is_bool($directiveValue)) {
                $domDirectivesChecked[$directiveName] = $directiveValue;
              }
              else {
                $message = sprintf("Invalid type for DOM directive parameter %s: Array or DOMNode expected, %s passed.", 
                  $directiveName,
                  AblePolecat_Data::getDataTypeName($directiveValue)
                );
                AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
              }
              break;
          }
        }
        //
        // Set defaults for missing parameters.
        // (fragmentParent and documentParent default to NULL)
        //
        if (!isset($domDirectivesChecked[self::DOM_DIRECTIVE_KEY_OP])) {
          $domDirectivesChecked[self::DOM_DIRECTIVE_KEY_OP] = self::DOM_FRAGMENT_OP_APPEND;
        }
        if (!isset($domDirectivesChecked[self::DOM_DIRECTIVE_KEY_FRAGMENT_PARENT])) {
          $domDirectivesChecked[self::DOM_DIRECTIVE_KEY_FRAGMENT_PARENT] = 'body';
        }
        if (!isset($domDirectivesChecked[self::DOM_DIRECTIVE_KEY_RECURSIVE])) {
          $domDirectivesChecked[self::DOM_DIRECTIVE_KEY_RECURSIVE] = TRUE;
        }
      }
      else {
        $message = sprintf("Bad parameter passed to %s #3 (domDirectives). Expected Array or NULL, %s passed.",
          __METHOD__,
          AblePolecat_Data::getDataTypeName($domDirectives)
        );
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::WARNING, $message);
      }
    }
    return $domDirectivesChecked;
  }
}