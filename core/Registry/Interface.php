<?php
/**
 * @file      polecat/core/Registry/Interface.php
 * @brief     Manages registry of supported interfaces and implementations.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server', 'Paths.php')));

class AblePolecat_Registry_Interface extends AblePolecat_RegistryAbstract {
  
  /**
   * @var AblePolecat_Registry_Interface Singleton instance.
   */
  private static $Registry = NULL;
  
  /**
   * @var List of Able Polecat interfaces.
   */
  private $Interfaces = NULL;
  
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
   * @return AblePolecat_Registry_Interface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Registry)) {
      try {
        self::$Registry = new AblePolecat_Registry_Interface($Subject);
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
    // Supported interfaces.
    //
    $this->Interfaces = array();
    
    $sql = __SQL()->          
      select('interfaceName')->
      from('interface');
    $Result = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    if($Result->success()) {
      $Interfaces = $Result->value();
      foreach($Interfaces as $key => $interface) {
        $this->Interfaces['interfaceName'] = $interface;
      }
    }
  }
}