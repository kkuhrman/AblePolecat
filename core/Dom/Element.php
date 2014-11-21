<?php
/**
 * @file      polecat/core/Dom/Element.php
 * @brief     Extends PHP DOMElement class.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom', 'Node.php')));

interface AblePolecat_Dom_ElementInterface extends AblePolecat_Dom_NodeInterface {
  /**
   * @return string Tag name of element.
   */
  public function getTagName();
  
  /**
   * @param string $tagName.
   */
  public function setTagName($tagName);
}