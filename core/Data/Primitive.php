<?php
/**
 * @file      polecat/core/Data/Primitive.php
 * @brief     Encapsulates primitive data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom', 'Node.php')));

interface AblePolecat_Data_PrimitiveInterface extends AblePolecat_Dom_NodeInterface, Serializable {
  /**
   * @return Data expressed as a string.
   */
  public function __toString();
  
  /**
   * Casts the given parameter into an instance of data class.
   *
   * @param mixed $data
   *
   * @return Concrete instance of AblePolecat_Data_PrimitiveInterface
   * @throw AblePolecat_Data_Exception if type cast is invalid.
   */
  public static function typeCast($data);
}