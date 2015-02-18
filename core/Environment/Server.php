<?php
/**
 * @file      polecat/core/Environment/Server.php
 * @brief     Environment for Able Polecat Server Mode.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Class.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'ClassLibrary.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment.php')));

class AblePolecat_Environment_Server extends AblePolecat_EnvironmentAbstract {
  
  const UUID = '318df280-5def-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Server Environment';
  
  /**
   * Variable names.
   */
  const VAR_REG_CLASS     = 'AblePolecat_Registry_Class';
  const VAR_REG_LIB       = 'AblePolecat_Registry_ClassLibrary';
      
  /**
   * @var AblePolecat_Environment_Server Singleton instance.
   */
  private static $Environment = NULL;
  
  /**
   * @var AblePolecat_Registry_Class
   */
  // private $ClassRegistry;
  
  /**
   * @var AblePolecat_Registry_ClassLibrary
   */
  // private $ClassLibraryRegistry;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
  }
  
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
   * @return AblePolecat_Environment_Server or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Environment)) {
      //
      // Initialize singleton instance.
      //
      self::$Environment = new AblePolecat_Environment_Server($Subject);
      
      //
      // Class library registry.
      //
      $ClassLibraryRegistry = AblePolecat_Registry_ClassLibrary::wakeup($Subject);
      self::$Environment->setVariable(
        $Subject,
        self::VAR_REG_LIB,
        $ClassLibraryRegistry
      );
      AblePolecat_Debug::kill($ClassLibraryRegistry);
      //
      // Class registry.
      //
      $ClassRegistry = AblePolecat_Registry_Class::wakeup($Subject);
      self::$Environment->setVariable(
        $Subject,
        self::VAR_REG_CLASS,
        $ClassRegistry
      );
    }
    return self::$Environment;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    parent::initialize();
  }
}
