<?php
/**
 * @file      polecat/core/Dom/Node.php
 * @brief     Extends PHP DOMNode class.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

interface AblePolecat_Dom_NodeInterface {
  /**
   * @return Data expressed as a string.
   */
  public function __toString();
  
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMNode Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL);
}