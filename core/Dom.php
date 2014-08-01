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
 * @version   0.5.0
 */

class AblePolecat_Dom {
  
  /**
   * @todo: XHTML 1.1 hard-coded here; should extend to allow for other doc types.
   */
  const XHTML_1_1_QUALIFIED_NAME  = 'html';
  const XHTML_1_1_PUBLIC_ID       = "-//W3C//DTD XHTML 1.1//EN";
  const XHTML_1_1_SYSTEM_ID       = "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd";
  const XHTML_1_1_NAMESPACE_URI   = NULL;
  
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
      throw new Exception(sprintf("Failed to merge element given by [%s] with document body.", $child->nodeName));
    }
    return $Node;
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
    $Element = $Elements->item(0)->firstChild;
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
   * die and vomit on screen.
   *
   * @param mixed $object A DOM object to examine.
   */
  public static function kill($object = NULL) {
    
    // global $Clock;
    
    $properties = array(
      'DOMDocument' => array(
        'actualEncoding',
        'config',
        'doctype',
        'documentElement',
        'documentURI',
        'encoding',
        'formatOutput',
        'implementation',
        'preserveWhiteSpace',
        'recover',
        'resolveExternals',
        'standalone',
        'strictErrorChecking',
        'substituteEntities',
        'validateOnParse',
        'version',
        'xmlEncoding',
        'xmlStandalone',
        'xmlVersion',
        'nodeName',
        'nodeValue',
        'nodeType',
        'parentNode',
        'childNodes',
        'firstChild',
        'lastChild',
        'previousSibling',
        'nextSibling',
        'attributes',
        'ownerDocument',
        'namespaceURI',
        'prefix',
        'localName',
        'baseURI',
        'textContent',
      ),
      'DOMNode' => array(
        'nodeName',
        'nodeValue',
        'nodeType',
        'parentNode',
        'childNodes',
        'firstChild',
        'lastChild',
        'previousSibling',
        'nextSibling',
        'attributes',
        'ownerDocument',
        'namespaceURI',
        'prefix',
        'localName',
        'baseURI',
        'textContent',
      ),
      'DOMElement' => array(
        'schemaTypeInfo',
        'tagName',
        'nodeName',
        'nodeValue',
        'nodeType',
        'parentNode',
        'childNodes',
        'firstChild',
        'lastChild',
        'previousSibling',
        'nextSibling',
        'attributes',
        'ownerDocument',
        'namespaceURI',
        'prefix',
        'localName',
        'baseURI',
        'textContent',
      ),
    );
    
    if (isset($object)) {
      $className = @get_class($object);
      if (isset($properties[$className])) {
        echo "<h2>$className</h2>";
        foreach($properties[$className] as $key => $varName) {          
          if (property_exists($object, $varName)) {
            $varValue = $object->$varName;
            $type = gettype($varValue);
            $varExport = '';
            if ($type == 'object') {
              $type = get_class($varValue);
              $varExport = 'Object';
            }
            else {
              switch ($type) {
                default:
                  $varExport = strval($varValue);
                  break;
                case 'boolean':
                  $varValue ? $varExport = 'true' : $varExport = 'false';
                  break;
              }
            }
            $out = sprintf("<pre class='xdebug-var-dump' dir='ltr'> <em>%s</em> <small>%s</small> <font color='#cc0000'>%s</font></pre>",
              $varName,
              $type,
              $varExport
            );
            echo $out;
          }
        }
      }
      else {
        var_dump($object);
      }
    }
    // print('<p><strong>stop: ' . $Clock->getElapsedTime(AblePolecat_Clock::ELAPSED_TIME_TOTAL_ACTIVE, TRUE) . '</strong></p>');
    die(__METHOD__);
  }
}