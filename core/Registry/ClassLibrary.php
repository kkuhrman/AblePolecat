<?php
/**
 * @file      polecat/core/Registry/ClassLibrary.php
 * @brief     Manages registry of third-pary class libraries used by modules.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server', 'Paths.php')));

class AblePolecat_Registry_ClassLibrary extends AblePolecat_RegistryAbstract {
  
  /**
   * @var AblePolecat_Registry_ClassLibrary Singleton instance.
   */
  private static $Registry = NULL;
  
  /**
   * @var List of Able Polecat modules.
   */
  private $ClassLibraries = NULL;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Registry_ClassLibrary Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Registry)) {
      try {
        self::$Registry = new AblePolecat_Registry_ClassLibrary($Subject);
      }
      catch (Exception $Exception) {
        self::$Registry = NULL;
        throw new AblePolecat_Registry_Exception($Exception->getMessage(), AblePolecat_Error::WAKEUP_FAIL);
      }
    }
    return self::$Registry;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
    /**
   * Extends constructor.
   */
  protected function initialize() {
    
    //
    // Supported modules.
    //
    $this->ClassLibraries = array();
    
    $sql = __SQL()->          
      select('classLibraryName', 'classLibraryId', 'classLibraryType', 'major', 'minor', 'revision', 'classLibraryDirectory')->
      from('classlib');
    $Result = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    if($Result->success()) {
      $ClassLibraries = $Result->value();
      foreach($ClassLibraries as $key => $classlib) {
        $classLibraryName = $classlib['classLibraryName'];
        $this->ClassLibraries[$classLibraryName] = $classlib;
      }
    }
  }
}