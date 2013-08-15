<?php
/**
 * @file: bootmode.php
 * Settings necessary to properly bootstrap Able Polecat in desired mode.
 */

//
// Path to Able Polecat library.
//
if (!defined('ABLE_POLECAT_PATH')) {
  $ABLE_POLECAT_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'libraries'  . DIRECTORY_SEPARATOR . 'Able Polecat';
  define('ABLE_POLECAT_PATH', $ABLE_POLECAT_PATH);
}

//
// Host context definitions (local or remote)
//
define('ABLE_POLECAT_LOCAL_HOST',   0x00000001);
define('ABLE_POLECAT_REMOTE_HOST',  0x00000002);

//
// Runtime context.
//
define('ABLE_POLECAT_RUNTIME_DEV',  0x00000004);
define('ABLE_POLECAT_RUNTIME_QA',   0x00000008);
define('ABLE_POLECAT_RUNTIME_USE',  0x00000010);
$ABLE_POLECAT_RUNTIME_CONTEXT = array(
  'dev' => ABLE_POLECAT_RUNTIME_DEV, 
  'qa'  => ABLE_POLECAT_RUNTIME_QA,
  'use' => ABLE_POLECAT_RUNTIME_USE,
);
$ABLE_POLECAT_RUNTIME_CONTEXT_STR = array(
  ABLE_POLECAT_RUNTIME_DEV => 'dev', 
  ABLE_POLECAT_RUNTIME_QA  => 'qa',
  ABLE_POLECAT_RUNTIME_USE => 'use',
);

//
// Error reporting
//
define('ABLE_POLECAT_ERROR_OFF',    0x00000100);
define('ABLE_POLECAT_ERROR_ON',     0x00000200);
define('ABLE_POLECAT_ERROR_CUSTOM', 0x00000400);

//
// Performance monitoring options
//
define('ABLE_POLECAT_MONITOR_TIME', 0x00001000);

/**
 * Helper function sets the global mode bit.
 */
function ABLE_POLECAT_SET_MODE($mode) {
  global $ABLE_POLECAT_MODE;
  if (is_int($mode) && (($ABLE_POLECAT_MODE & $mode) == 0)) {
    $ABLE_POLECAT_MODE |= $mode;
  }
}

/**
 * Helper function returns TRUE if given mode bit is set otherwise FALSE.
 */
function ABLE_POLECAT_IS_MODE($mode) {
  global $ABLE_POLECAT_MODE;
  $is_mode = FALSE;
  if (is_int($mode)) {
     if (($ABLE_POLECAT_MODE & $mode) != 0) {
      $is_mode = TRUE;
     }
  }
  return $is_mode;
}

/**
 * Used to set/unset default exception handler.
 */
function ABLE_POLECAT_SET_EXCEPTION_HANDLER($Handler = 'ABLE_POLECAT_EXCEPTION_HANDLER_DEFAULT') {
  set_exception_handler($Handler);
}

/**
 * Used to log exceptions thrown before user logger(s) initialized.
 */
function ABLE_POLECAT_EXCEPTION_HANDLER_DEFAULT($Exception) {
  //
  // open syslog, include the process ID and also send
  // the log to standard error, and use a user defined
  // logging mechanism
  //
  openlog("AblePolecat", LOG_PID | LOG_ERR, LOG_USER);

  //
  // log the exception
  //
  $access = date("Y/m/d H:i:s");
  $message = $Exception->getMessage();
  syslog(LOG_WARNING, "Able Polecat, $access, {$_SERVER['REMOTE_ADDR']},  ({$_SERVER['HTTP_USER_AGENT']}), $message");
  closelog();
}

/**
 * Used to set/unset default error handler.
 */
function ABLE_POLECAT_SET_ERROR_HANDLER($Handler = 'ABLE_POLECAT_ERROR_HANDLER_DEFAULT') {
  set_error_handler($Handler);
}

/**
 * Used to handle errors encountered while running in production mode.
 */
function ABLE_POLECAT_ERROR_HANDLER_DEFAULT($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = NULL) {
  
  $die = (($errno == E_ERROR) || ($errno == E_USER_ERROR));
  
  //
  // Get error information
  //
  $msg = sprintf("Error in Able Polecat. %d %s", $errno, $errstr);
  isset($errfile) ? $msg .= " in $errfile" : NULL;
  isset($errline) ? $msg .= " line $errline" : NULL;
  isset($errcontext) ? $msg .= ' : ' . serialize($errcontext) : NULL;
  
  //
  // Send error information to syslog
  //
  openlog("AblePolecat", LOG_PID | LOG_ERR, LOG_USER);
  syslog(LOG_ERR, $msg);
  closelog();
  
  if ($die) {
    die('arrrgghh...');
  }
  
  return $die;
}

/**
 * Helper function uses a cookie to store local dev/test mode settings.
 */
function ABLE_POLECAT_RUNTIME_CONTEXT_COOKIE_SET($runtime_context) {
  //
  // @todo: Do nothing if agent is not browser.
  //
  if (isset($runtime_context)) {
    if (isset($_COOKIE['ABLE_POLECAT_RUNTIME'])) {
      //
      // Compare current cookie setting to parameter
      //
      $data = unserialize($_COOKIE['ABLE_POLECAT_RUNTIME']);
      isset($data['context']) ? $stored_runtime_context = $data['context'] : NULL;
      if ($runtime_context != $stored_runtime_context) {
        //
        // Setting changed, first expire cookie
        //
        setcookie('ABLE_POLECAT_RUNTIME', '', time() - 3600);
      }
    }
    $data = array('context' => $runtime_context);
    setcookie('ABLE_POLECAT_RUNTIME', serialize($data), time() + 3600);    
  }
  else if (isset($_COOKIE['ABLE_POLECAT_RUNTIME'])) {
    //
    // Expire any runtime context cookie
    //
    setcookie('ABLE_POLECAT_RUNTIME', '', time() - 3600);
  }
}

/**
 * Helper function starts monitoring script execution time.
 */
global $ABLE_POLECAT_CLOCK;
function ABLE_POLECAT_CLOCK_START() {
	include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Clock.php');
	global $ABLE_POLECAT_CLOCK;
	$ABLE_POLECAT_CLOCK = new AblePolecat_Clock();
	$ABLE_POLECAT_CLOCK->start();
}

/**
 * Helper function returns ellapsed time since clock start as string.
 */
function ABLE_POLECAT_CLOCK_PRINT() {
	global $ABLE_POLECAT_CLOCK;
	$ellapsed_time = NULL;
	if (isset($ABLE_POLECAT_CLOCK)) {
		$ellapsed_time = $ABLE_POLECAT_CLOCK->getElapsedTime(AblePolecat_Clock::ELAPSED_TIME_TOTAL_ACTIVE, TRUE);
	}
	return $ellapsed_time;
}

/**
 * Helper function for desperate developers only.
 */
function panic($msg = 'arrrrrrgghh...', $die = TRUE) {
  
  if ($die) {
    $trace = debug_backtrace();
    var_dump($trace);
    die(print_r($msg, TRUE));
  }
  else {
    print sprintf("<p>%s</p>", print_r($msg, TRUE));
  }
}

//
// Define PHP ERROR reporting level
// ABLE_POLECAT_ERROR_ON | ABLE_POLECAT_ERROR_OFF | ABLE_POLECAT_ERROR_CUSTOM
//
ABLE_POLECAT_SET_MODE(ABLE_POLECAT_ERROR_ON);

//
// Monitor script execution time (Y/N)?
//
ABLE_POLECAT_SET_MODE(ABLE_POLECAT_MONITOR_TIME);

//
// Initialize error reporting
//
if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_ERROR_ON)) {
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
}
else if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_ERROR_OFF)) {
	error_reporting(0);
}
else {
	//
	// define custom error reporting level here...
	//
	!ABLE_POLECAT_IS_MODE(ABLE_POLECAT_ERROR_CUSTOM) ? ABLE_POLECAT_SET_MODE(ABLE_POLECAT_ERROR_CUSTOM) : NULL;
  ABLE_POLECAT_SET_ERROR_HANDLER();
}

//
// Default exception handler (until application loggers are initialized).
//
ABLE_POLECAT_SET_EXCEPTION_HANDLER();

//
// Initialize performance monitoring options
//
if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_MONITOR_TIME)) {
	ABLE_POLECAT_CLOCK_START();
}

//
// Runtime context.
//
if (isset($_GET['run'])) {
  switch ($_GET['run']) {
    default:
      ABLE_POLECAT_SET_MODE(ABLE_POLECAT_RUNTIME_USE);
      break;
    case 'dev':
      ABLE_POLECAT_SET_MODE(ABLE_POLECAT_RUNTIME_DEV);
      break;
    case 'qa':
      ABLE_POLECAT_SET_MODE(ABLE_POLECAT_RUNTIME_QA);
      break;
  }
}
else if (isset($_COOKIE['ABLE_POLECAT_RUNTIME'])) {
  //
  // If runtime context was saved in a cookie, use that until agent
  // explicitly unsets with run=use or cookie expires.
  //
  $data = unserialize($_COOKIE['ABLE_POLECAT_RUNTIME']);
  isset($data['context']) ? $runtime_context = $data['context'] : NULL;
  switch ($runtime_context) {
    case ABLE_POLECAT_RUNTIME_DEV:
    case ABLE_POLECAT_RUNTIME_QA:
      ABLE_POLECAT_SET_MODE($runtime_context);
      break;
    default:
      ABLE_POLECAT_SET_MODE(ABLE_POLECAT_RUNTIME_USE);
      break;
  }
}
else {
    //
    // Otherwise, override runtime context here for Cron or service testing.
    // ABLE_POLECAT_RUNTIME_DEV | ABLE_POLECAT_RUNTIME_QA | ABLE_POLECAT_RUNTIME_USE
    //
    ABLE_POLECAT_SET_MODE(ABLE_POLECAT_RUNTIME_USE);
}
