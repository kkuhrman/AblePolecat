<?php
/**
 * @file      polecat/core/Dom/Document/Html.php
 * @brief     Extends HTML version of PHP DOMDocument class.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom', 'Document.php')));

interface AblePolecat_Dom_Document_HtmlInterface extends AblePolecat_Dom_DocumentInterface {
}

class AblePolecat_Dom_Document_Html 
  extends AblePolecat_Dom_DocumentAbstract 
  implements AblePolecat_Dom_Document_HtmlInterface {
  
  /**
   *  Create Empty Document.
   *
   * @return AblePolecat_Dom_DocumentInterface. 
   */
  public static function create() {
  }
  
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
  public static function createFromFile($filename, $options = 0) {
    $Document = new AblePolecat_Dom_Document_Html();
    $Document->Document = DOMDocument::loadHTMLFile($filename, $options);
    return $Document;
  }
  
  /**
   * Create Document from a string.
   * 
   * @param string $source The HTML string.
   * @param int $options  Additional Libxml parameters.
   *
   * @return AblePolecat_Dom_DocumentInterface. 
   */
  public static function createFromString($source, $options = 0) {
    $Document = new AblePolecat_Dom_Document_Html();
    $Document->Document = DOMDocument::loadHTML($source, $options);
    return $Document;
  }
  
  /**
   *  Save the DOM tree into a file.
   *
   * @param string $filename The path to the saved XML document.
   * @param int $options Additional Options. Currently only LIBXML_NOEMPTYTAG is supported. 
   *
   * @return Returns the number of bytes written or FALSE if an error occurred.
   */
  public function saveAsFile($filename, $options = 0) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->saveHTMLFile($filename);
    }
    return $returnValue;
  }
  
  /**
   * Save the internal DOM tree back as a string.
   *
   * @param DOMNode $node
   * @param int $options
   * 
   * @return mixed Returns XML as string, or FALSE if an error occurred.
   */
  public function saveAsString(DOMNode $node, $options = 0) {
    $returnValue = FALSE;
    if (isset($this->Document)) {
      $returnValue = $this->Document->saveHTML($node);
    }
    return $returnValue;
  }
}