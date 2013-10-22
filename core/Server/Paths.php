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
// Path to Able Polecat data directory.
//
if (!defined('ABLE_POLECAT_DATA_PATH')) {
  $ABLE_POLECAT_DATA_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'data';
  define('ABLE_POLECAT_DATA_PATH', $ABLE_POLECAT_DATA_PATH);
}

//
// Path to Able Polecat development tools.
//
if (!defined('ABLE_POLECAT_DEV_PATH')) {
  $ABLE_POLECAT_DEV_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'dev';
  define('ABLE_POLECAT_DEV_PATH', $ABLE_POLECAT_DEV_PATH);
}

//
// Path to third party class libraries.
//
if (!defined('ABLE_POLECAT_LIBS_PATH')) {
  $ABLE_POLECAT_LIBS_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'libs';
  define('ABLE_POLECAT_LIBS_PATH', $ABLE_POLECAT_LIBS_PATH);
}

//
// Log files directory.
//
if (!defined('ABLE_POLECAT_LOGS_PATH')) {
  $ABLE_POLECAT_LOGS_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'logs';
  define('ABLE_POLECAT_LOGS_PATH', $ABLE_POLECAT_LOGS_PATH);
}

//
// Path to contributed modules directory.
//
if (!defined('ABLE_POLECAT_MODS_PATH')) {
  $ABLE_POLECAT_MODS_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'mods';
  define('ABLE_POLECAT_MODS_PATH', $ABLE_POLECAT_MODS_PATH);
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
   * Path to third party class libraries.
   */
  const libs = ABLE_POLECAT_LIBS_PATH;
  
  /**
   * Path to log files directory.
   */
  const logs = ABLE_POLECAT_LOGS_PATH;
  
  /**
   * Path to contributed modules directory.
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
   * @var Array Directories used for data storage.
   */
  private static $data_paths = array();
  
  /**
   * @var Array Some paths are set in server conf file.
   */
  private static $conf_paths = array();
  
  /**
   * Attempt to create configurable directory if it does not exist.
   *
   * @param string $path Full (unsanitized) path of directory or regular file to create.
   * @param bool $dir If true, check for directory; otherwise check for regular file.
   * @param octal $mode Access restrictions for creating directory.
   *
   * @return mixed Full path if created or exists, otherwise FALSE.
   * @throw AblePolecat_Server_Paths_Exception if absent directory could not be created.
   */
  public static function touch($path, $dir = TRUE, $mode = 0644) {
    
    $full_path = FALSE;
    $sanitized_path = self::sanitizePath($path);
    if (file_exists($sanitized_path) && ($dir == is_dir($sanitized_path))) {
      $full_path = $sanitized_path;
    }
    else {
      $made = FALSE;
      switch ($dir) {
        case TRUE:
          $made = @mkdir($sanitized_path, $mode);
          break;
        case FALSE;
          $made = fopen($sanitized_path, 'r');
          break;
      }
      
      if ($made) {
        $full_path = $sanitized_path;
      }
      else {
        throw new AblePolecat_Server_Paths_Exception("Failed attempt to create directory $sanitized_path.",
          AblePolecat_Error::MKDIR_FAIL);
      }
    }
    return $full_path;
  }
  
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
          else if (isset(self::$data_paths[$subdir])) {
            $path = self::$data_paths[$subdir];
          }
          else {
            throw new AblePolecat_Server_Paths_Exception("Attempt to access $subdir directory before configurable paths initialized.",
              AblePolecat_Error::BOOT_SEQ_VIOLATION);
          }
          break;
        //
        // Preset sub-directories
        //
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
  
  /**
   * Check if given sub-directory name is reserved (preset).
   *
   * @param string $subdir Name of system directory.
   *
   * @return bool TRUE if the given sub-directory name is reserved, otherwise FALSE.
   */
  public static function isPresetDir($subdir) {
    
    $reserved = TRUE;
    switch ($subdir) {
      default:
        $reserved = FALSE;
        break;
      //
      // Preset sub-directories
      //
      case 'root':
      case 'conf':
      case 'core':
      case 'dev':
      case 'libs':
      case 'logs':
      case 'mods':
      case 'qa':
      case 'user':
      case 'sites':
      case 'services':
        break;
    }
    return $reserved;
  }
  
  /**
   * Override default location of certain sub-directories.
   *
   * @param string $subdir Name of system directory.
   * @param string $fullpath Full path to given sub directory.
   */
  public static function setFullPath($subdir, $fullpath) {
    
    if (isset($subdir) && !self::isPresetDir($subdir)) {
      
      try {
        switch($subdir) {
          default:
            $new_path = self::touch($fullpath);
            self::$conf_paths[$subdir] = $new_path;
            break;
          case 'data':
            $new_path = self::touch($fullpath);
            self::$data_paths['data'] = $new_path;
            break;
          case 'session':
            $new_path = self::touch($fullpath);
            self::$data_paths['session'] = $new_path;
            break;
        }
      }
      catch (AblePolecat_Server_Paths_Exception $Exception) {
        AblePolecat_Server::log('warning', $Exception->getMessage());
      }
    }
    else {
      AblePolecat_Server::log('warning', "Cannot set [$subdir] as configurable directory.");
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
    
    foreach(self::$conf_paths as $name => $path) {
      if (file_exists($path) && is_dir($path)) {
        continue;
      }
      else {
        echo "User configured directory named $name does not exist at $path.";
        AblePolecat_Server::log('warning', "User configured directory named $name does not exist at $path.");
      }
    }
  }
}

/**
 * Exceptions thrown by Able Polecat relating to system paths.
 */
class AblePolecat_Server_Paths_Exception extends AblePolecat_Exception {
}