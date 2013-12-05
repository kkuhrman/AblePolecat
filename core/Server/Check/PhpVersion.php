<?php
/**
 * @file: PhpVersion.php
 * Check if current PHP version meets Able Polecat core requirement.
 */

include_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Check.php');

class AblePolecat_Server_Check_PhpVersion extends AblePolecat_Server_CheckAbstract {
  
  /**
   * @return TRUE if check passed, otherwise FALSE.
   */
  public static function go() {
    
    $go = FALSE;
    
    //
    // Check PHP version
    //
    $php_version = phpversion();
    $php_version_parts = explode('.', $php_version);
    if (isset($php_version_parts[0]) && isset($php_version_parts[1]) && 5 === intval($php_version_parts[0])) {
      switch (intval($php_version_parts[1])) {
        case 2:
        case 3:
          $go = TRUE;
          break;
        default:
          goto ABLE_POLECAT_CHECK_FAIL;
          break;
      }
    }
    
ABLE_POLECAT_CHECK_FAIL:
    return $go;
  }
}