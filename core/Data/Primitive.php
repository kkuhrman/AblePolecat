<?php
/**
 * @file      polecat/core/Data/Primitive.php
 * @brief     Encapsulates primitive data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data.php')));

interface AblePolecat_Data_PrimitiveInterface extends Serializable {
  /**
   * Casts the given parameter into an instance of data class.
   *
   * @param mixed $data
   *
   * @return Concrete instance of AblePolecat_Data_PrimitiveInterface
   * @throw AblePolecat_Data_Exception if type cast is invalid.
   */
  public static function typeCast($data);
  
  /**
   * @return Data expressed as a string.
   */
  public function __toString();
  
  /**
   * @param DOMDocument $Document.
   * @param string $tagName Name of element tag (default is data type).
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document, $tagName = NULL);
}