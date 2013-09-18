<?php
/**
 * @file: core_require.php
 * Files required by core Able Polecat classes.
 */

//
// Exceptions and Errors
//
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'core', 'Exception.php')));

//
// Check PHP version
//
$php_version = phpversion();
$php_version_parts = explode('.', $php_version);
$php_supported = FALSE;
if (isset($php_version_parts[0]) && isset($php_version_parts[1]) && 5 === intval($php_version_parts[0])) {
  switch (intval($php_version_parts[1])) {
    case 2:
    case 3:
      $php_supported = TRUE;
      break;
    default:
      break;
  }
}
if (!$php_supported) {
  throw new AblePolecat_Exception("PHP version $php_version is not supported by Able Polecat",
    AblePolecat_Error::UNSUPPORTED_PHP_VER);
}

//
// Sets paths for entire framework; must be first
//
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'core', 'ClassRegistry.php')));
