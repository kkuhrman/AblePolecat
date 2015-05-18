<?php
/**
 * @file      polecat/core/Dom/Node.php
 * @brief     Extends PHP DOMNode class.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

interface AblePolecat_Dom_NodeInterface {
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMNode Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL);
}