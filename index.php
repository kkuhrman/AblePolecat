<?php
/**
 * @file: index.php
 * Default point of entry for Able Polecat.
 *
 * All Able Polecat Project software is released under the BSD 2 License.
 * @see: LICENSE.md.
 */
 
/**
 * Root directory of the entire Able Polecat core project.
 */
// if (!defined('ABLE_POLECAT_ROOT')) {
  // $ABLE_POLECAT_ROOT = __DIR__;
  // define('ABLE_POLECAT_ROOT', $ABLE_POLECAT_ROOT);
// }

/**
 * Location of Able Polecat core class library.
 * Override this definition if you intend to locate the core class library
 * anywhere other than ABLE_POLECAT_ROOT/core.
 */
// if (!defined('ABLE_POLECAT_CORE')) {
  // $ABLE_POLECAT_CORE = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core';
  // define('ABLE_POLECAT_CORE', $ABLE_POLECAT_CORE);
// }

/**
 * Host-specific system-wide configuration files directory.
 * This constant IS used to locate the server configuration file and must define 
 * the full path of the parent of the conf directory if other than ABLE_POLECAT_ROOT/etc/conf.
 */
// if (!defined('ABLE_POLECAT_ETC')) {
  // $ABLE_POLECAT_ETC = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'etc';
  // define('ABLE_POLECAT_ETC', $ABLE_POLECAT_ETC);
// }

/**
 * Variable files directory.
 * This constant is used to locate files with content expected to continually change 
 * during normal operation of the system, such as logs. It must define the full path 
 * to a directory, for which the web agent has write privilege if other than 
 * ABLE_POLECAT_ROOT/files.
 */
// if (!defined('ABLE_POLECAT_FILES')) {
  // $ABLE_POLECAT_FILES = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'files';
  // define('ABLE_POLECAT_FILES', $ABLE_POLECAT_FILES);
// }

/**
 * Secondary directory hierarchy contains third-party modules, custom pages, services, 
 * utilities, etc.
 */
// if (!defined('ABLE_POLECAT_USR')) {
  // $ABLE_POLECAT_USR = ABLE_POLECAT_ROOT  . DIRECTORY_SEPARATOR . 'usr';;
  // define('ABLE_POLECAT_USR', $ABLE_POLECAT_USR);
// }

/**
 * Bootstrap Able Polecat.
 * Override this if you redefine the constant ABLE_POLECAT_CORE like so:
 * require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server.php')));
 */
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'core', 'Server.php')));

try {
  AblePolecat_Server::bootstrap();
}
catch (AblePolecat_Exception $Exception) {
  AblePolecat_Server::redirect(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_FILES, 'html', 'install', 'home.html')));
}
?>