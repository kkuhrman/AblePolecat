<?php 
/**
 * @file      polecat/core/Server/Paths.php
 * @brief     Encapsulates Able Polecat server, application and user path utilities.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

/**
 * Root directory of the entire Able Polecat core project.
 */
if (!defined('ABLE_POLECAT_ROOT')) {
  $message = sprintf("Global variable %s should be defined in index.php.", 'ABLE_POLECAT_ROOT');
  trigger_error($message, E_USER_ERROR);
}

/**
 * Host-specific system-wide configuration files directory.
 */
if (!defined('ABLE_POLECAT_ETC')) {
  $message = sprintf("Global variable %s should be defined in index.php.", 'ABLE_POLECAT_ETC');
  trigger_error($message, E_USER_ERROR);
}

/**
 * Variable files directory.
 */
if (!defined('ABLE_POLECAT_VAR')) {
  $message = sprintf("Global variable %s should be defined in path.config.", 'ABLE_POLECAT_VAR');
  trigger_error($message, E_USER_ERROR);
}

/**
 * Secondary directory hierarchy contains third-party modules, custom pages, services, 
 * utilities, etc.
 */
if (!defined('ABLE_POLECAT_USR')) {
  $message = sprintf("Global variable %s should be defined in path.config.", 'ABLE_POLECAT_USR');
}

/**
 * This is the root directory containing all the interface implementations and 
 * extension class source files.
 */
if (!defined('ABLE_POLECAT_SRC')) {
  $message = sprintf("Global variable %s should be defined in path.config.", 'ABLE_POLECAT_SRC');
}

/**
 * Top-level paths configuration file full path.
 * This is used to differentiate from library and module path config file paths.
 */
if (!defined('ABLE_POLECAT_ROOT_PATH_CONF_FILE_PATH')) {
  $ABLE_POLECAT_ROOT_PATH_CONF_FILE_PATH = implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_ETC, 'polecat', 'conf', 'path.config'));
  define('ABLE_POLECAT_ROOT_PATH_CONF_FILE_PATH', $ABLE_POLECAT_ROOT_PATH_CONF_FILE_PATH);
}

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Server', 'Paths.php')));

class AblePolecat_Server_Paths {

  /**
   * Able Polecat root directory.
   */
  const root = ABLE_POLECAT_ROOT;
  
  /**
   * Path to main Able Polecat class library.
   */
  const core = ABLE_POLECAT_CORE;
  
  /**
   * Path to Able Polecat system configuration files.
   */
  const etc = ABLE_POLECAT_ETC;
  
  /**
   * Path to third-pary and custom modules/code.
   */
  const usr = ABLE_POLECAT_USR;
  
  /**
   * Path to project source files.
   */
  const src = ABLE_POLECAT_SRC;
  
  /**
   * Path to log files directory.
   * 'files' is an alias for 'var', which violates keyword constraints in PHP.
   */
  const files = ABLE_POLECAT_VAR;
  
  /**
   * Configuration file constants.
   */
  const CONF_FILENAME_PROJECT     = 'project.xml';
  
  /**
   * @var Array Directories used for data storage.
   */
  private static $data_paths = array();
  
  /**
   * @var Array Some paths are set in environment conf files.
   */
  private static $conf_paths = array();
  
  /**
   * @return Array Paths used for data storage.
   */
  public static function getDataPaths() {
    return self::$data_paths;
  }
  
  /**
   * @return Array Paths set in environment conf files.
   */
  public static function getConfPaths() {
    return self::$conf_paths;
  }
  
  /**
   * Includes file if found, gives application a chance to fail gracefully if not.
   *
   * @param $file_name Name of file to include.
   * @param $default_directory_name Name of default directory to look in.
   * @param $directories Optional Array of other directories to search.
   * @param $once If TRUE calls include_once(), otherwise calls include().
   *
   * @return string Full path name of included file or FALSE.
   */
  public static function includeFile($file_name,
    $default_directory_name = ABLE_POLECAT_CORE, 
    $directories = array(),
    $once = TRUE) {
    
    $ret = FALSE;
    
    //
    // First assume full path passed as first parameter.
    //
    $search_path = $file_name;
    if (self::verifyFile($search_path)) {
      $once ? include_once($search_path) : include($search_path);
      $ret = $search_path;
      // $filePath = self::getFullPath('log') . DIRECTORY_SEPARATOR . 'includes.txt';
      // $fout = @fopen($filePath, 'a');
      // fwrite($fout, "$ret\n");
      // fclose($fout);
    }
    else {
      //
      // Search for file using hints provided by other parameters.
      //
      $search_path = $default_directory_name . DIRECTORY_SEPARATOR . $file_name;
      if (self::verifyFile($search_path)) {
        $once ? include_once($search_path) : include($search_path);
        $ret = $search_path;
      }
      else if (isset($directories) && is_array($directories)){
        foreach($directories as $key => $directory) {
          $ret = self::includeFile($file_name, $directory, NULL, $once);
          if ($ret) {
            break;
          }
        }
      }
    }
    return $ret;
  }
  
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
          $made = fopen($sanitized_path, 'a+');
          break;
      }
      
      if ($made) {
        $full_path = $sanitized_path;
      }
      else {
        throw new AblePolecat_Server_Paths_Exception("Failed attempt to create directory $sanitized_path.");
      }
    }
    return $full_path;
  }
  
  /**
   * Given relative path, return full path based on current server/application environment.
   *
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
            $paths = explode(PATH_SEPARATOR, get_include_path());
            foreach($paths as $key => $searchPath) {
              if (file_exists($searchPath . DIRECTORY_SEPARATOR . $subdir)) {
                $path = $searchPath . DIRECTORY_SEPARATOR . $subdir;
                self::$conf_paths[$subdir] = $path;
              }
            }
          }
          break;
        //
        // Preset sub-directories
        //
        case 'root':
          $path = self::root;
          break;
        case 'etc':
          $path = self::etc;
          break;
        case 'core':
          $path = self::core;
          break;
        case 'lib':
          $path = implode(DIRECTORY_SEPARATOR, array(self::usr, 'lib'));
          break;
        case 'conf':
          $path = implode(DIRECTORY_SEPARATOR, array(self::etc, 'polecat', 'conf'));
          break;
        case 'log':
          $path = implode(DIRECTORY_SEPARATOR, array(self::files, 'log'));
          break;
        case 'files':
        case 'var':
          $path = self::files;
          break;
        case 'usr':
          $path = self::usr;
          break;
        case 'src':
          $path = self::src;
          break;
        case 'mod':
          $path = implode(DIRECTORY_SEPARATOR, array(self::usr, 'mod'));
          break;
        case 'test':
          $path = implode(DIRECTORY_SEPARATOR, array(self::usr, 'share', 'test'));
          break;
        case 'doc':
          $path = implode(DIRECTORY_SEPARATOR, array(self::usr, 'share', 'documentation'));
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
      case 'etc':
      case 'core':
      case 'conf';
      case 'log':
      case 'lib':
      case 'usr':
      case 'src':
      case 'files':
      case 'var':
      case 'mod':
      case 'test':
      case 'doc':
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
    else {
      throw new AblePolecat_Server_Paths_Exception("Cannot set [$subdir] as configurable directory.");
    }
  }
  
  /**
   * Remove dangerous characters and localize path string.
   *
   * @param string $path The path to sanitize.
   *
   * @return string A sanitized path or NULL.
   */
  public static function sanitizePath($path) {
    //
    // Remove leading, trailing white space.
    //
    $sanitized_path = trim($path);
    
    //
    // Localize path separators. First attempt assumes UNIX style path.
    //
    $replacements = 0;
    $sanitized_path = str_replace ('/', DIRECTORY_SEPARATOR, $sanitized_path, $replacements);
    if ($replacements === 0) {
      //
      // No replacements performed. Assume Windows style path.
      //
      $sanitized_path = str_replace ('\\', DIRECTORY_SEPARATOR, $sanitized_path, $replacements);
    }
    return $sanitized_path;
  }
  
  /**
   * Verify that the given path points to an existing, accessible directory.
   * 
   * @param string $path
   *
   * @return bool TRUE if $path is a valid directory otherwise FALSE.
   */
  public static function verifyDirectory($path) {
    return file_exists($path) && is_dir($path);
  }
  
  /**
   * Verify that the given path points to an existing, accessible regular file.
   * 
   * @param string $path
   *
   * @return bool TRUE if $path is a valid regular file otherwise FALSE.
   */
  public static function verifyFile($path) {
    return file_exists($path) && is_file($path);
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
        throw new AblePolecat_Server_Paths_Exception("User configured directory named $name does not exist at $path.");
      }
    }
  }
  
  final protected function __construct() {}
}