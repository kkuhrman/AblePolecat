<?php
/**
 * @file: Integer.php
 * Encapsulates an integer variable.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Data', 'Scalar.php')));

class  AblePolecat_Data_Scalar_Integer extends AblePolecat_Data_Scalar {
  
  /**
   * Casts the given parameter into an instance of data class.
   *
   * @param mixed $data
   *
   * @return Concrete instance of AblePolecat_DataInterface
   * @throw AblePolecat_Data_Exception if type cast is invalid.
   */
  public static function typeCast($data) {
    
    $Data = NULL;
    
    is_numeric($data) ? $cast = intval($data) : $cast = NULL;
    if (isset($cast)) {
      $Data = new AblePolecat_Data_Scalar_Integer($cast);
    }
    else {
      throw new AblePolecat_Data_Exception(
        sprintf("Cannot cast %s as integer.", gettype($data)), 
        AblePolecat_Error::INVALID_TYPE_CAST
      );
    }
    
    return $Data;
  }
  
  /**
   * @return string Data expressed as a string.
   */
  public function __toString() {
    return sprintf("%d", $this->getData());
  }
}