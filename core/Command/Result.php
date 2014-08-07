<?php
/**
 * @file      polecat/Command/Result.php
 * @brief     Encapsulates success/failure status of command execution and return result.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

class AblePolecat_Command_Result {
  
  const RESULT_RETURN_SUCCESS = TRUE;
  const RESULT_RETURN_FAIL    = FALSE;
  
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