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
// Error reporting
//
define('ABLE_POLECAT_ERROR_OFF',    0x00000010);
define('ABLE_POLECAT_ERROR_ON',     0x00000020);
define('ABLE_POLECAT_ERROR_CUSTOM', 0x00000040);

//
// Performance monitoring options
//
define('ABLE_POLECAT_MONITOR_TIME', 0x00000100);

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
}

//
// Initialize performance monitoring options
//
if (ABLE_POLECAT_IS_MODE(ABLE_POLECAT_MONITOR_TIME)) {
	ABLE_POLECAT_CLOCK_START();
}