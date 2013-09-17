<?php
/**
 * @file: Exception.php
 * Base class for all Able Polecat Exceptions.
 */
 
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'Error.php');

/**
 * Default exception thrown in Able Polecat.
 */
class AblePolecat_Exception extends Exception {
  
  const ABLE_POLECAT_EXCEPTION_BASE         = 0x00010000;
  
  /**
   * @var string Name of class in which exception was thrown.
   */
  private $m_class;
  
  /**
   * @var string Name of method/function in which exception was thrown.
   */
  private $m_function;
  
  public function __construct($message, $code = AblePolecat_Error::NO_ERROR_CODE_GIVEN, Exception $previous = null) {
    $backtrace = $this->getTrace();
    !isset($message) ? $message = ABLE_POLECAT_EXCEPTION_MSG($code) : NULL;
    isset($backtrace[1]['class']) ? $this->m_class = $backtrace[1]['class'] : $this->m_class = NULL;
    isset($backtrace[1]['function']) ? $this->m_function = $backtrace[1]['function'] : $this->m_function = NULL;
    parent::__construct($message, $code | AblePolecat_Exception::ABLE_POLECAT_EXCEPTION_BASE, $previous);
  }

  public function __toString() {
    $std_message = sprintf("Exception thrown in Able Polecat. \"%s\" CODE %d",
      $this->message,
      $this->code);
    isset($this->m_class) ? $std_message .= ' ' . $this->m_class . '::' : NULL;
    isset($this->m_function) ? $std_message .= $this->m_function . '()' : NULL;
    return $std_message;
  }
}