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
   * @var Array Some paths are set in server conf file.
   */
  private static $conf_paths = array(
  );
  
  /**
   * @param string $subdir Name of system directory.
   *
   * @return string Full path of given system directory or NULL.
   */
  public static function getFullPath($subdir) {
    
    $path = NULL;
    if (isset($subdir) && is_string($subdir)) {
      switch($subdir) {
        default:
          //
          // Configurable (not core) paths.
          //
          if (isset(self::$conf_paths[$subdir])) {
            $path = self::$conf_paths[$subdir];
          }
          else {
            throw new AblePolecat_Server_Paths_Exception("Attempt to access $subdir directory before configurable paths initialized.",
              ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION);
          }
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
  
  /**
   * Override default location of certain sub-directories.
   *
   * @param string $subdir Name of system directory.
   * @param string $fullpath Full path to given sub directory.
   */
  public static function setFullPath($subdir, $fullpath) {
    switch($subdir) {
      default:
        break;
      case 'libs':
        self::$conf_paths['libs'] = self::sanitizePath($fullpath);
        break;
      case 'logs':
        self::$conf_paths['logs'] = self::sanitizePath($fullpath);
        break;
      case 'mods':
        self::$conf_paths['mods'] = self::sanitizePath($fullpath);
        break;
    }
  }
  
  /**
   * @todo:
   * @param string $path The path to sanitize.
   * @return string A sanitized path or NULL.
   * @throw AblePolecat_Server_Paths_Exception if paths is not valid.
   */
  public static function sanitizePath($path) {
    $sanitized_path = trim($path);
    return $sanitized_path;
  }
  
  /**
   * User/configurable directories.
   */
  public static function verifyConfDirs() {
    //
    // Contributed libraries directory.
    //
    if (!isset(self::$conf_paths['libs'])) {
        self::$conf_paths['libs'] = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'libs';
    }
    if (!file_exists(self::$conf_paths['libs'])) {
      mkdir(self::$conf_paths['libs']);
    }
    
    //
    // Log files directory.
    //
    if (!isset(self::$conf_paths['logs'])) {
        self::$conf_paths['logs'] = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'logs';
    }
    if (!file_exists(self::$conf_paths['logs'])) {
      mkdir(self::$conf_paths['logs']);
    }
    
    //
    // Contributed modules directory.
    //
    if (!isset(self::$conf_paths['mods'])) {
        self::$conf_paths['mods'] = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'mods';
    }
    if (!file_exists(self::$conf_paths['mods'])) {
      mkdir(self::$conf_paths['mods']);
    }
  }
}

/**
 * Exceptions thrown by Able Polecat relating to system paths.
 */
class AblePolecat_Server_Paths_Exception extends AblePolecat_Exception {
}