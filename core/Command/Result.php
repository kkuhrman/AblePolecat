<?php
/**
 * @file: Command/Result.php
 * Encapsulates success/failure status of command execution and return result.
 */

class AblePolecat_Command_Result {
  
  // const RESULT_RETURN         = 'return';
  const RESULT_RETURN_SUCCESS = TRUE;
  const RESULT_RETURN_FAIL    = FALSE;
  // const RESULT_DATA           = 'data';
  
  /**
   * @var RESULT_RETURN_SUCCESS | RESULT_RETURN_FAIL
   */
  private $result;
  
  /**
   * @var return value of executed command.
   */
  private $value;
  
  public function success() {
    return $this->result;
  }
  
  public function value() {
    return $this->value;
  }
  
  final public function __construct($value = NULL, $result = self::RESULT_RETURN_FAIL) {
    switch ($result) {
      default:
        $this->result = self::RESULT_RETURN_FAIL;
        break;
      case self::RESULT_RETURN_SUCCESS:
      case self::RESULT_RETURN_FAIL:
        $this->result = $result;
        break;
    }
    $this->value = $value;
  }
}