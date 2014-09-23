<?php
/**
 * @file      polecat/core/Data/Scalar/String.php
 * @brief     Encapsulates both text data types.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar.php')));

class  AblePolecat_Data_Scalar_String extends AblePolecat_Data_ScalarAbstract {
  
  const ESC_CHARSET_PHP = 'ESC_CHARSET_PHP';
  const ESC_CHARSET_CSV = 'ESC_CHARSET_CSV';
  const ESC_CHARSET_SQL = 'ESC_CHARSET_SQL';

  /**
   * Some ASCII char 'hints' for idiots.
   */
  const ASCII_BACKSLASH = 0x5C;
  const ASCII_SINGLE_QUOTE = 0x27;
  const ASCII_DOUBLE_QUOTE = 0x22;
  const ASCII_NEW_LINE = 0x0A;
  const ASCII_CARRIAGE_RETURN = 0x0D;
  const ASCII_TAB = 0x09;
  const ASCII_FORM_FEED = 0x0C;
  
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
    
    if (is_scalar($data)) {
      $Data = new AblePolecat_Data_Scalar_String(strval($data));
    }
    else if (is_object($data) && method_exists($data, '__toString')) {
      $Data = new AblePolecat_Data_Scalar_String(strval($data->__toString()));
    }
    else {
      throw new AblePolecat_Data_Exception(
        sprintf("Cannot cast %s as string.", gettype($data)), 
        AblePolecat_Error::INVALID_TYPE_CAST
      );
    }
    
    return $Data;
  }
  
  /**
   * Helper function returns CRLF.
   */
  public static function CRLF() {
    return sprintf("%c%c", 13, 10);
  }
  
  /**
   * Removes all characters from a string except letters (A-Z, a-z)
   *
   * @param mixed $input The input text.
   *
   * @return AblePolecat_Data_Scalar_String The output text.
   */
  public static function lettersOnly($input) {
    return AblePolecat_Data_Scalar_String::typeCast(trim(preg_replace('/[^A-Z a-z_]/', '', $input)));
  }

  /**
   * Remove any non-numeric characters from a string
   *
   * @param mixed $input The input text.
   *
   * @return AblePolecat_Data_Scalar_String The output text.
   */
  public static function removeNonNumeric($input, $strict = TRUE) {
    switch ($strict) {
      case TRUE:
        return AblePolecat_Data_Scalar_String::typeCast(preg_replace('/\D/', '', $input));
      case FALSE:
        return AblePolecat_Data_Scalar_String::typeCast(trim(preg_replace('/[^0-9.,]/', '', $input)));
    }
  }
  
  /**
   * @return string Data expressed as a string.
   */
  public function __toString() {
    return sprintf("%s", $this->getData());
  }
}