<?php
/**
 * @file: Xml.php
 * Stores class registry in an XML file.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'ClassRegistry.php')));

class AblePolecat_ClassRegistry_Xml extends AblePolecat_ClassRegistryAbstract {
  
  /**
   * @var AblePolecat_ClassRegistry Singleton instance.
   */
  private static $ClassRegistry = NULL;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    parent::initialize();
    //
    // @todo: populate class from XML file
    // @see: setRegisteredClass()
    //
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // @todo: get file handle
    //
    $registeredClasses = $this->getRegisteredClasses();
    foreach($registeredClasses as $class_name => $class_info) {
      $result = FALSE;
      isset($class_info[self::CLASS_REG_PATH]) ? $path = $class_info[self::CLASS_REG_PATH] : $path = NULL; 
      isset($class_info[self::CLASS_REG_METHOD]) ? $method = $class_info[self::CLASS_REG_METHOD] : $method = NULL;
      if (isset($path) && isset($method)) {
        //
        // todo: add class registration to DOM
        //
      }
    }
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$ClassRegistry)) {
      self::$ClassRegistry = new AblePolecat_ClassRegistry_Xml();
    }
    return self::$ClassRegistry;
  }
}