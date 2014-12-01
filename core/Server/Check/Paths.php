<?php
/**
 * @file      polecat/core/Server/Check/Paths.php
 * @brief     Check Able Polecat paths.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server', 'Check.php')));

class AblePolecat_Server_Check_Paths extends AblePolecat_Server_CheckAbstract {
  
  /**
   * @return TRUE if check passed, otherwise FALSE.
   */
  public static function go() {
    
    $go = TRUE;
    
    if (!defined('ABLE_POLECAT_ROOT')) {
      self::$error_code = AblePolecat_Error::SYS_PATH_ERROR;
      self::$error_message = "Global variable is not defined: ABLE_POLECAT_ROOT";
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, self::$error_message);
    }
    else if (!self::directoryExists(ABLE_POLECAT_ROOT)) {
      self::$error_code = AblePolecat_Error::SYS_PATH_ERROR;
      self::$error_message = sprintf("Invalid system path encountered: $dir", ABLE_POLECAT_ROOT);
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::ERROR, self::$error_message);
    }
    foreach ($dirs as $key => $dir) {
      if () {
        self::$error_code = AblePolecat_Error::SYS_PATH_ERROR;
        
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