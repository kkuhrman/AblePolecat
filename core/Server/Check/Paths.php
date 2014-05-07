<?php
/**
 * @file      polecat/core/Server/Check/Paths.php
 * @brief     Check Able Polecat paths.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server', 'Check.php')));

class AblePolecat_Server_Check_Paths extends AblePolecat_Server_CheckAbstract {
  
  /**
   * @return TRUE if check passed, otherwise FALSE.
   */
  public static function go() {
    
    $go = TRUE;
    
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
        self::$error_code = AblePolecat_Error::SYS_PATH_ERROR;
        self::$error_message = "Invalid system path encountered: $dir";
        $go = FALSE;
        goto ABLE_POLECAT_CHECK_FAIL;
      }
    }
    
ABLE_POLECAT_CHECK_FAIL:
    return $go;
  }
  
  /**
   * @param string $dir Full path of directory.
   * @return TRUE if directory exists, otherwise FALSE.
   */
  public static function directoryExists($dir) {
    return (file_exists($dir) && is_dir($dir));
  }
}