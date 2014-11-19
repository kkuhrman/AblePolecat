<?php
/**
 * @file: Integer.php
 * Encapsulates an integer variable.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar.php')));

class  AblePolecat_Data_Scalar_Integer extends AblePolecat_Data_ScalarAbstract {
  
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
   * Helper function - given array of integers, return highest value.
   *
   * @todo: based on http://php.net/manual/en/array.sorting.php there is some disagreement as
   * to which native PHP sorting algorithm is fastest, most efficient etc. We are sticking with
   * a home-grown algorithm so as to use typeCast for excluding not-scalar values.
   *
   * @param Array $numbers
   *
   * @return int Highest value or FALSE.
   * @throw AblePolecat_Data_Exception if not-scalar element is included in array.
   */
  public static function max($numbers) {
    
    $maxValue = FALSE;
    if (is_array($numbers) && isset($numbers[0])) {
      $maxValue = AblePolecat_Data_Scalar_Integer::typeCast($numbers[0]);
      foreach($numbers as $key => $value) {
        $Number = AblePolecat_Data_Scalar_Integer::typeCast($value);
        if ($Number->getData() > $maxValue->getData()) {
          $maxValue = $Number;
        }
      }
    }
    else {
      throw new AblePolecat_Data_Exception(
        sprintf("%s parameter must be Array[scalar]. %s passed", __METHOD__, gettype($data))
      );
    }
    return $maxValue->getData();
  }
  
  /**
   * @return string Data expressed as a string.
   */
  public function __toString() {
    return sprintf("%d", $this->getData());
  }
}