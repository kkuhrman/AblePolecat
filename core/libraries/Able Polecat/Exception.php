<?php
/**
 * @file: Exception.php
 * Base class for all Able Polecat Exceptions.
 */

/**
 * Able Polecat exception codes.
 */

/**
 * Unknown exception.
 */
define('ABLE_POLECAT_EXCEPTION_UNKNOWN', 0);

/**
 * Violation of bootstrap procedure sequence rules.
 */
define('ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION', 10);

/**
 * Invalid boot file path encountered.
 */
define('ABLE_POLECAT_EXCEPTION_BOOT_PATH_INVALID', 100);

/**
 * Invalid loadable class registration.
 */
define('ABLE_POLECAT_EXCEPTION_BOOTSTRAP_CLASS_REG', 101);

/**
 * Failure to set logger.
 */
define('ABLE_POLECAT_EXCEPTION_BOOTSTRAP_LOGGER', 102);

/**
 * Failure to initialize application access control agent.
 */
define('ABLE_POLECAT_EXCEPTION_BOOTSTRAP_AGENT', 103);

/**
 * Failure to access/set application configuration.
 */
define('ABLE_POLECAT_EXCEPTION_BOOTSTRAP_CONFIG', 104);

/**
 * Failure to start session.
 */
define('ABLE_POLECAT_EXCEPTION_BOOTSTRAP_SESSION', 105);

/**
 * Failure to open application database.
 */
define('ABLE_POLECAT_EXCEPTION_BOOTSTRAP_DB', 106);

/**
 * Failure to bring service bus online.
 */
define('ABLE_POLECAT_EXCEPTION_BOOTSTRAP_BUS', 107);

/**
 * Failure to return a current environment object.
 */
define('ABLE_POLECAT_EXCEPTION_GET_CURRENT_ENV', 108);

/**
 * Failure to return a environment member object.
 */
define('ABLE_POLECAT_EXCEPTION_GET_MEMBER', 109);

/**
 * Invalid path for contributed class libraries.
 */
define('ABLE_POLECAT_EXCEPTION_LIBS_PATH_INVALID', 110);

/**
 * Invalid path for log files.
 */
define('ABLE_POLECAT_EXCEPTION_LOGS_PATH_INVALID', 111);

/**
 * Invalid path for contributed modules.
 */
define('ABLE_POLECAT_EXCEPTION_MODS_PATH_INVALID', 112);

/**
 * Session unavailable.
 */
define('ABLE_POLECAT_EXCEPTION_UNAVAILABLE', 201);

/**
 * Session not started.
 */
define('ABLE_POLECAT_EXCEPTION_NOT_STARTED', 202);

/**
 * Failed to decode session.
 */
define('ABLE_POLECAT_EXCEPTION_DECODE_FAIL', 203);

/**
 * Failed to open specified syslog.
 */
define('ABLE_POLECAT_EXCEPTION_SYSLOG', 301);

/**
 * No default logger.
 */
define('ABLE_POLECAT_EXCEPTION_NO_DEF_LOG', 302); 

/**
 * Failed to open log file.
 */
define('ABLE_POLECAT_EXCEPTION_LOG_OPEN_FAIL', 303);

/**
 * Attempt to log to an invalid stream.
 */
define('ABLE_POLECAT_EXCEPTION_LOG_INVALID', 304);

/**
 * @return Default message for given exception code.
 */
function ABLE_POLECAT_EXCEPTION_MSG($code = NULL) {
  $message = array(
    ABLE_POLECAT_EXCEPTION_UNKNOWN => 'Exception thrown in Able Polecat.',
    ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION => 'Bootstrap procedure sequence violation.',
    ABLE_POLECAT_EXCEPTION_BOOT_PATH_INVALID => 'Invalid boot file path encountered.',
    ABLE_POLECAT_EXCEPTION_BOOTSTRAP_CLASS_REG => 'Invalid loadable class registration.',
    ABLE_POLECAT_EXCEPTION_BOOTSTRAP_LOGGER => 'Failure to set logger.',
    ABLE_POLECAT_EXCEPTION_BOOTSTRAP_AGENT => 'Failure to initialize application access control agent.',
    ABLE_POLECAT_EXCEPTION_BOOTSTRAP_CONFIG => 'Failure to access/set application configuration.',
    ABLE_POLECAT_EXCEPTION_BOOTSTRAP_SESSION => 'Failure to start session.',
    ABLE_POLECAT_EXCEPTION_BOOTSTRAP_DB => 'Failure to open application database.',
    ABLE_POLECAT_EXCEPTION_BOOTSTRAP_BUS => 'Failure to bring service bus online.',
    ABLE_POLECAT_EXCEPTION_GET_CURRENT_ENV => 'Failure to return a current environment object.',
    ABLE_POLECAT_EXCEPTION_GET_MEMBER => 'Failure to return a environment member object.',
    ABLE_POLECAT_EXCEPTION_LIBS_PATH_INVALID => 'Invalid path for contributed class libraries.',
    ABLE_POLECAT_EXCEPTION_MODS_PATH_INVALID => 'Invalid path for contributed modules.',
    ABLE_POLECAT_EXCEPTION_LOGS_PATH_INVALID => 'Invalid path for log files.',
    ABLE_POLECAT_EXCEPTION_UNAVAILABLE => 'Session unavailable.',
    ABLE_POLECAT_EXCEPTION_NOT_STARTED => 'Session not started.',
    ABLE_POLECAT_EXCEPTION_DECODE_FAIL => 'Failed to decode session.',
    ABLE_POLECAT_EXCEPTION_SYSLOG => 'Failed to open syslog.',
    ABLE_POLECAT_EXCEPTION_NO_DEF_LOG => 'No default logger.',
    ABLE_POLECAT_EXCEPTION_LOG_OPEN_FAIL => 'Failed to open log file.',
    ABLE_POLECAT_EXCEPTION_LOG_INVALID => 'Attempt to log to an invalid stream.',
  );

  if (isset($code) && isset($message[$code])) {
    return $message[$code];
  }
  else {
    return $message[ABLE_POLECAT_EXCEPTION_UNKNOWN];
  }
}

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
  
  public function __construct($message, $code = ABLE_POLECAT_EXCEPTION_UNKNOWN, Exception $previous = null) {
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