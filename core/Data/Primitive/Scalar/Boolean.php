<?php
/**
 * @file      polecat/core/Data/Primitive/Scalar/Boolean.php
 * @brief     Encapsulates an boolean variable.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Primitive', 'Scalar.php')));

class  AblePolecat_Data_Primitive_Scalar_Boolean extends AblePolecat_Data_Primitive_ScalarAbstract {
  
  /********************************************************************************
   * Implementation of AblePolecat_Data_PrimitiveInterface.
   ********************************************************************************/
   
  /**
   * Casts the given parameter into an instance of data class.
   *
   * @param mixed $data
   *
   * @return Concrete instance of AblePolecat_Data_PrimitiveInterface
   * @throw AblePolecat_Data_Exception if type cast is invalid.
   */
  public static function typeCast($data) {
    
    $Data = NULL;
    
    if (is_bool($data)) {
      $Data = new AblePolecat_Data_Primitive_Scalar_Boolean($data);
    }
    else {
      if (is_scalar($data)) {
        $cast = TRUE;
        if (0 == intval($data)) {
          $cast = FALSE;
        }
        $Data = new AblePolecat_Data_Primitive_Scalar_Boolean($cast);
      }
      else {
        throw new AblePolecat_Data_Exception(
          sprintf("Cannot cast %s as %s.", AblePolecat_Data::getDataTypeName($data), __CLASS__), 
          AblePolecat_Error::INVALID_TYPE_CAST
        );
      }
    }    
    
    return $Data;
  }
  
  /**
   * @return string Data expressed as a string.
   */
  public function __toString() {
    $this->getData() ? $strVal = 'true' : $strVal = 'false';
    return $strVal;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
}