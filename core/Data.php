<?php
/**
 * @file      polecat/core/Data.php
 * @brief     Encapsulates both scalar and not scalar data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Data.php')));

/**
 * Static data handling methods.
 */
class AblePolecat_Data {
  
  /**
   * Given a variable, return it's native data type name.
   *
   * @param mixed $variable The variable for which type check is requested.
   *
   * @return string Name of given data type.
   */
  public static function getDataTypeName($variable = NULL) {
    
    $typeName = 'null';
    
    if (isset($variable)) {
      $typeName = @gettype($variable);
      if ($typeName === 'object') {
        $typeName = @get_class($variable);
      }
    }
    return $typeName;
  }
}

interface AblePolecat_DataInterface extends Serializable {
  
  /**
   * @param DOMDocument $Document.
   * @param string $tagName Name of element tag (default is data type).
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document, $tagName = NULL);
}