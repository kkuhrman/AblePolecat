<?php 
/**
 * @file: Paths.php
 * Encapsulates Able Polecat system path definitions and rules.
 */

//
// Root directory.
//
if (!defined('ABLE_POLECAT_ROOT')) {
  $ABLE_POLECAT_ROOT = dirname(dirname(__DIR__));
  define('ABLE_POLECAT_ROOT', $ABLE_POLECAT_ROOT);
}

//
// Path to Able Polecat core class library.
//
if (!defined('ABLE_POLECAT_PATH')) {
  $ABLE_POLECAT_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'core';
  define('ABLE_POLECAT_PATH', $ABLE_POLECAT_PATH);
}

//
// Path to Able Polecat system configuration files.
//
if (!defined('ABLE_POLECAT_CONF_PATH')) {
  $ABLE_POLECAT_CONF_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'conf';
  define('ABLE_POLECAT_CONF_PATH', $ABLE_POLECAT_CONF_PATH);
}

//
// Path to Able Polecat development tools.
//
if (!defined('ABLE_POLECAT_DEV_PATH')) {
  $ABLE_POLECAT_DEV_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'dev';
  define('ABLE_POLECAT_DEV_PATH', $ABLE_POLECAT_DEV_PATH);
}

//
// Contributed libraries directory.
//
if (!defined('ABLE_POLECAT_LIBS_PATH')) {
  $ABLE_POLECAT_LIBS_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'libs';
  define('ABLE_POLECAT_LIBS_PATH', $ABLE_POLECAT_LIBS_PATH);
  if (!file_exists(ABLE_POLECAT_LIBS_PATH)) {
    mkdir(ABLE_POLECAT_LIBS_PATH);
  }
}

//
// Log files directory.
//
if (!defined('ABLE_POLECAT_LOGS_PATH')) {
  $ABLE_POLECAT_LOGS_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'logs';
  define('ABLE_POLECAT_LOGS_PATH', $ABLE_POLECAT_LOGS_PATH);
  if (!file_exists(ABLE_POLECAT_LOGS_PATH)) {
    mkdir(ABLE_POLECAT_LOGS_PATH);
  }
}

//
// Contributed modules directory.
//
if (!defined('ABLE_POLECAT_MODS_PATH')) {
  $ABLE_POLECAT_MODS_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'mods';
  define('ABLE_POLECAT_MODS_PATH', $ABLE_POLECAT_MODS_PATH);
  if (!file_exists(ABLE_POLECAT_MODS_PATH)) {
    mkdir(ABLE_POLECAT_MODS_PATH);
  }
}

//
// Path to Able Polecat unit test and qa directory.
//
if (!defined('ABLE_POLECAT_QA_PATH')) {
  $ABLE_POLECAT_QA_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'qa';
  define('ABLE_POLECAT_QA_PATH', $ABLE_POLECAT_QA_PATH);
}

//
// Path to Able Polecat user resource directory.
//
if (!defined('ABLE_POLECAT_USER_PATH')) {
  $ABLE_POLECAT_USER_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'user';
  define('ABLE_POLECAT_USER_PATH', $ABLE_POLECAT_USER_PATH);
}

//
// Path to Able Polecat user sites files.
//
if (!defined('ABLE_POLECAT_SITES_PATH')) {
  $ABLE_POLECAT_SITES_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'user'  . DIRECTORY_SEPARATOR . 'sites';
  define('ABLE_POLECAT_SITES_PATH', $ABLE_POLECAT_SITES_PATH);
}

//
// Path to Able Polecat user services files.
//
if (!defined('ABLE_POLECAT_SERVICES_PATH')) {
  $ABLE_POLECAT_SERVICES_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'user'  . DIRECTORY_SEPARATOR . 'services';
  define('ABLE_POLECAT_SERVICES_PATH', $ABLE_POLECAT_SERVICES_PATH);
}

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

class AblePolecat_Server_Paths {

  /**
   * Able Polecat root directory.
   */
  const root = ABLE_POLECAT_ROOT;
  
  /**
   * Path to Able Polecat system configuration files.
   */
  const conf = ABLE_POLECAT_CONF_PATH;
  
  /**
   * Path to main Able Polecat class library.
   */
  const core = ABLE_POLECAT_PATH;
  
  /**
   * Path to Able Polecat development tools.
   */
  const dev = ABLE_POLECAT_DEV_PATH;
  
  /**
   * Contributed libraries directory.
   */
  const libs = ABLE_POLECAT_LIBS_PATH;
  
  /**
   * Log files directory.
   */
  const logs = ABLE_POLECAT_LOGS_PATH;
  
  /**
   * Contributed modules directory.
   */
  const mods = ABLE_POLECAT_MODS_PATH;
  
  /**
   * Path to Able Polecat unit test and qa directory.
   */
  const qa = ABLE_POLECAT_QA_PATH;
  
  /**
   * Path to Able Polecat user resource directory.
   */
  const user = ABLE_POLECAT_USER_PATH;
  
  /**
   * Path to Able Polecat user sites files.
   */
  const sites = ABLE_POLECAT_SITES_PATH;
  
  /**
   * Path to Able Polecat user services files.
   */
  const services = ABLE_POLECAT_SERVICES_PATH;
  
  /**
   * @param string $subdir Name of system directory.
   * @return string Full path of given system directory or NULL.
   */
  public static function getFullPath($subdir) {
    
    $path = NULL;
    if (isset($subdir) && is_string($subdir)) {
      switch($subdir) {
        default:
          break;
        case 'root':
          $path = self::root;
          break;
        case 'conf':
          $path = self::conf;
          break;
        case 'core':
          $path = self::core;
          break;
        case 'dev':
          $path = self::dev;
          break;
        case 'libs':
          $path = self::libs;
          break;
        case 'logs':
          $path = self::logs;
          break;
        case 'mods':
          $path = self::mods;
          break;
        case 'qa':
          $path = self::qa;
          break;
        case 'user':
          $path = self::user;
          break;
        case 'sites':
          $path = self::sites;
          break;
        case 'services':
          $path = self::services;
          break;
      }
    }
    return $path;
  }
}

/**
 * Exceptions thrown by Able Polecat relating to system paths.
 */
class AblePolecat_Server_Paths_Exception extends AblePolecat_Exception {
}