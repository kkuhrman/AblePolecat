<?php
/**
 * @file      polecat/core/Exception.php
 * @brief     Base class for all Able Polecat Exceptions.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */
 
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Error.php');

define('ABLE_POLECAT_EXCEPTION_MSG_DELIMITER', ' ' . DIRECTORY_SEPARATOR . ' ');

/**
 * Default exception thrown in Able Polecat.
 */
class AblePolecat_Exception extends Exception {
  
  const ABLE_POLECAT_EXCEPTION_BASE         = 0x00010000;
  
  protected function appendDebugData($input) {
    //
    // @todo: some mode-based conditional?
    //
    
    $output = $input;
    
    //
    // Append debugging data to message
    //
    $stack_id = 1;
    if (isset($backtrace[$stack_id]['class'])) {
      $output .= ABLE_POLECAT_EXCEPTION_MSG_DELIMITER . $backtrace[$stack_id]['class'];
      if (isset($backtrace[$stack_id]['function'])) {
        $output .= '::' . $backtrace[$stack_id]['function'];
      }
    }
    else if (isset($backtrace[$stack_id]['function'])) {
      $output .= ABLE_POLECAT_EXCEPTION_MSG_DELIMITER . $backtrace[$stack_id]['function'];
    }
    else if (isset($backtrace[$stack_id]['file'])) {
      $output .= ABLE_POLECAT_EXCEPTION_MSG_DELIMITER . $backtrace[$stack_id]['file'];
    }
    isset($backtrace[$stack_id]['line']) ? $output .= $backtrace[$stack_id]['line'] : NULL;
    
    return $output;
  }
  
  public function __construct($message, $code = AblePolecat_Error::NO_ERROR_CODE_GIVEN, Exception $previous = null) {
    //
    // Set default message if not given.
    //
    !isset($message) ? $message = ABLE_POLECAT_EXCEPTION_MSG($code) : NULL;
    
    $message = $this->appendDebugData($message);
    
    parent::__construct($message, $code | AblePolecat_Exception::ABLE_POLECAT_EXCEPTION_BASE, $previous);
  }

  public function __toString() {
    return $this->appendDebugData(sprintf("Exception thrown in Able Polecat. \"%s\" CODE %d",
      $this->message,
      $this->code)
    );
  }
}