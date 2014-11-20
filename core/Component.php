<?php
/**
 * @file      polecat/core/Component.php
 * @brief     A specialized DOM element sub-class for not-scalar resource properties.
 * 
 * The Component class provides a solution for merging not-scalar resource properties into 
 * template elements. Not-scalar properties include data structures such as lists and tables. 
 * In order to support late-binding of these data structures with presentation syntax in 
 * a template, the Component class allows designer to express a presentation syntax for a 
 * single table row,list item , etc. in the template, which will be extracted by the Component 
 * class at runtime and used as a micro template for embedding the data structure into the 
 * rendered document.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode', 'Component.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom', 'Element.php')));

interface AblePolecat_ComponentInterface 
  extends AblePolecat_Dom_ElementInterface,
          AblePolecat_AccessControl_Article_StaticInterface {
}

abstract class AblePolecat_ComponentAbstract 
  extends AblePolecat_Dom_ElementAbstract 
  implements AblePolecat_ComponentInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Scope of operation.
   *
   * @return string.
   */
  public static function getScope() {
    return 'APPLICATION';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Data_PrimitiveInterface.
   ********************************************************************************/
  
  /**
   * @param DOMDocument $Document.
   * @param string $tagName Name of element tag (default is data type).
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document, $tagName = NULL) {
    //
    // @todo:
    //
    return parent::getDomNode($Document, $tagName);
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
}