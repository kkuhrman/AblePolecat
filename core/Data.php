<?php
/**
 * @file      AblePolecat/core/Data.php
 * @brief     Encapsulates routines for type-checking and casting.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive', 'Array.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive', 'Scalar', 'Boolean.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive', 'Scalar', 'Integer.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive', 'Scalar', 'Null.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive', 'Scalar', 'String.php')));
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
  
  /**
   * Cast PHP primitive data type as Able Polecat primitive data type.
   *
   * @param mixed $data PHP primitive data type.
   *
   * @return AblePolecat_Data_PrimitiveInterface Able Polecat primitive data type.
   * @throw AblePolecat_Data_Exception If cast cannot be made.
   */
  public static function castPrimitiveType($data) {
    
    $primitive = NULL;
    switch (gettype($data)) {
      default:
        // 'resource'
        // 'NULL'
        throw new AblePolecat_Data_Exception(
          sprintf("Cannot cast %s as string.", self::getDataTypeName($data)), 
          AblePolecat_Error::INVALID_TYPE_CAST
        );
        break;
      case 'NULL':
        $primitive = AblePolecat_Data_Primitive_Scalar_Null::typeCast($data);
        break;
      case 'boolean':
        $primitive = AblePolecat_Data_Primitive_Scalar_Boolean::typeCast($data);
        break;
      case 'integer':
        $primitive = AblePolecat_Data_Primitive_Scalar_Integer::typeCast($data);
        break;
      case 'double':
      case 'string':
        $primitive = AblePolecat_Data_Primitive_Scalar_String::typeCast($data);
        break;
      case 'object':
        $primitive = AblePolecat_Data_Primitive_StdObject::typeCast($data);
        break;
      case 'array':
        $primitive = AblePolecat_Data_Primitive_Array::typeCast($data);
        break;
    }
    return $primitive;
  }
}