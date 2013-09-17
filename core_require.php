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
// Sets paths for entire framework; must be first
//
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'core', 'ClassRegistry.php')));
