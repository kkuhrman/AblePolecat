<?php
/**
 * @file      polecat/core/Version.php
 * @brief     Encapsulates info for the current version of Able Polecat core.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

/**
 * Most current version is loaded from conf file. These are defaults.
 */
define('ABLE_POLECAT_VERSION_NAME', 'DEV-0.6.3');
define('ABLE_POLECAT_VERSION_ID', 'ABLE_POLECAT_CORE_0_6_3_DEV');
define('ABLE_POLECAT_VERSION_MAJOR', '0');
define('ABLE_POLECAT_VERSION_MINOR', '6');
define('ABLE_POLECAT_VERSION_REVISION', '3');

final class AblePolecat_Version {
  
  /**
   * AblePolecat_Version INstance of singleton.
   */
  private static $Version;
  
  /**
   * @var string Version number from server config settings file.
   */
  private $info;
  
  /**
   * Get version number of server/core.
   */
  public static function getVersion($as_str = TRUE, $doc_type = 'XML') {
    
    $info = NULL;
    
    if (!isset(self::$Version)) {
      self::$Version = new AblePolecat_Version();
    }
    
    if ($as_str) {
      switch ($doc_type) {
        default:
          $info = sprintf("Able Polecat core %s.%s.%s (%s)",
            self::$Version->info['major'],
            self::$Version->info['minor'],
            self::$Version->info['revision'],
            self::$Version->info['name']
          );
          break;
        case 'XML':
          $info = sprintf("<polecat_version name=\"%s\"><major>%s</major><minor>%s</minor><revision>%s</revision></polecat_version>",
            self::$Version->info['name'],
            strval(self::$Version->info['major']),
            strval(self::$Version->info['minor']),
            strval(self::$Version->info['revision'])
          );
          break;
      }
    }
    else {
      $info = self::$Version->info;
    }
    
    return $info;
  }
  
  protected function __construct() {
    $this->info = array(
      'name' => ABLE_POLECAT_VERSION_NAME,
      'major' => ABLE_POLECAT_VERSION_MAJOR,
      'minor' => ABLE_POLECAT_VERSION_MINOR,
      'revision' => ABLE_POLECAT_VERSION_REVISION,
    );
  }
}