<?php
/**
 * @file: Paths.php
 * Check Able Polecat paths.
 */

include_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Check.php');

class AblePolecat_Server_Check_Paths extends AblePolecat_Server_CheckAbstract {
  
  /**
   * @return TRUE if check passed, otherwise FALSE.
   */
  public static function go() {
    
    $pathsAreGo = TRUE;
    
    $dirs = array(
      AblePolecat_Server_Paths::root,
      AblePolecat_Server_Paths::conf,
      AblePolecat_Server_Paths::core,
      AblePolecat_Server_Paths::dev,
      AblePolecat_Server_Paths::qa,
      AblePolecat_Server_Paths::user,
      AblePolecat_Server_Paths::sites,
      AblePolecat_Server_Paths::services,
      
      //
      // @todo: these paths are configurable
      //
      // AblePolecat_Server_Paths::libs,
      // AblePolecat_Server_Paths::logs,
      // AblePolecat_Server_Paths::mods,
    );
    foreach ($dirs as $key => $dir) {
      if (!self::directoryExists($dir)) {
        self::$error_code = ABLE_POLECAT_EXCEPTION_SYS_PATH_ERROR;
        self::$error_message = "Invalid system path encountered: $dir";
        $pathsAreGo = FALSE;
        goto ABLE_POLECAT_CHECK_FAIL;
      }
    }
    
ABLE_POLECAT_CHECK_FAIL:
    return $pathsAreGo;
  }
  
  /**
   * @param string $dir Full path of directory.
   * @return TRUE if directory exists, otherwise FALSE.
   */
  public static function directoryExists($dir) {
    return (file_exists($dir) && is_dir($dir));
  }
}