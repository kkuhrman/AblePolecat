<?php
/**
 * @file: local.php
 * Settings specific to local installation.
 *
 * In some cases it may be practical to separate application directories from the 
 * core class library or share the core with more than one application. In such 
 * cases the defines in this file can be uncommented and edited to define the 
 * application specific paths and location of core class library.
 */

//
// Location of local installation root directory.
//
// if (!defined('ABLE_POLECAT_LOCAL')) {
  // define('ABLE_POLECAT_LOCAL', __DIR__);
// }

//
// Define location of Able Polecat configuration directory.
//
// if (!defined('ABLE_POLECAT_CONF_PATH')) {
//   define('ABLE_POLECAT_CONF_PATH', ABLE_POLECAT_LOCAL . DIRECTORY_SEPARATOR . 'conf');
//}

//
// Define location of Able Polecat data directory.
//
// if (!defined('ABLE_POLECAT_DATA_PATH')) {
//   define('ABLE_POLECAT_DATA_PATH', ABLE_POLECAT_LOCAL . DIRECTORY_SEPARATOR . 'data');
//}

//
// Define location of directory for Able Polecat development tools.
//
// if (!defined('ABLE_POLECAT_DEV_PATH')) {
//   define('ABLE_POLECAT_DEV_PATH', ABLE_POLECAT_LOCAL . DIRECTORY_SEPARATOR . 'dev');
//}

//
// Path to third party class libraries.
//
// if (!defined('ABLE_POLECAT_LIBS_PATH')) {
//   define('ABLE_POLECAT_LIBS_PATH', ABLE_POLECAT_LOCAL . DIRECTORY_SEPARATOR . 'libs');
//}

//
// Log files directory.
//
// if (!defined('ABLE_POLECAT_LOGS_PATH')) {
//   define('ABLE_POLECAT_LOGS_PATH', ABLE_POLECAT_LOCAL . DIRECTORY_SEPARATOR . 'logs');
//}

//
// Path to contributed modules directory.
//
// if (!defined('ABLE_POLECAT_MODS_PATH')) {
//   define('ABLE_POLECAT_MODS_PATH', ABLE_POLECAT_LOCAL . DIRECTORY_SEPARATOR . 'mods');
//}

//
// Define location of Able Polecat unit test and qa directory.
//
// if (!defined('ABLE_POLECAT_QA_PATH')) {
//   define('ABLE_POLECAT_QA_PATH', ABLE_POLECAT_LOCAL . DIRECTORY_SEPARATOR . 'qa');
//}

//
// Define location of user directory.
//
// if (!defined('ABLE_POLECAT_USER_PATH')) {
//   define('ABLE_POLECAT_USER_PATH', ABLE_POLECAT_LOCAL . DIRECTORY_SEPARATOR . 'user');
//}

//
// Define location of user sites directory.
//
// if (!defined('ABLE_POLECAT_SITES_PATH')) {
//   define('ABLE_POLECAT_SITES_PATH', ABLE_POLECAT_USER_PATH . DIRECTORY_SEPARATOR . 'sites');
//}

//
// Define location of user services directory.
//
// if (!defined('ABLE_POLECAT_SERVICES_PATH')) {
//   define('ABLE_POLECAT_SERVICES_PATH', ABLE_POLECAT_USER_PATH . DIRECTORY_SEPARATOR . 'services');
//}

//
// Define location of Able Polecat root directory (contains boot.php).
//
// if (!defined('ABLE_POLECAT_ROOT')) {
//   define('ABLE_POLECAT_ROOT', implode(DIRECTORY_SEPARATOR, array(dirname(__DIR__), 'public')));
//}

//
// Define location of Able Polecat core class library.
//
// if (!defined('ABLE_POLECAT_PATH')) {
//   define('ABLE_POLECAT_PATH',  ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core');
//}