<?php
/**
 * @file: ClassRegistry.php
 * Handles registration and lazy loading of Able Polecat classes.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Server', 'Paths.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'CacheObject.php')));

class AblePolecat_ClassRegistry extends AblePolecat_CacheObjectAbstract {
  
    /**
   * Class registration constants.
   */
  const CLASS_REG_PATH    = 'path';
  const CLASS_REG_METHOD  = 'method';
  
  /**
   * @var AblePolecat_ClassRegistry Singleton instance.
   */
  private static $ClassRegistry = NULL;
   
  
  /**
   * @var Array Registry of classes which can be loaded.
   */
  private $m_loadable_classes;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    $this->m_loadable_classes = array();
  }
  
  /**
   * Check if class can be loaded in current environment.
   * 
   * @param string $class_name The name of class to check for.
   *
   * @return Array include file path and creation method, otherwise FALSE.
   */
  public function isLoadable($class_name) {
    
    $response = FALSE;
    if (isset($this->m_loadable_classes[$class_name])) {
      $response = $this->m_loadable_classes[$class_name];
    }
    return $response;
  }
  
  /**
   * Get instance of given class.
   *
   * @param string $class_name The name of class to instantiate.
   * 
   * @return object Instance of given class or NULL.
   */
  public function loadClass($class_name) {
    
    $Instance = NULL;
    $info = self::isLoadable($class_name);
    if (isset($info[self::CLASS_REG_METHOD])) {
      switch ($info[self::CLASS_REG_METHOD]) {
        default:
          $Instance = call_user_func(array($class_name, $info[self::CLASS_REG_METHOD]));
          break;
        case '__construct':
          $Instance = new $class_name;
          break;
      }
    }
    return $Instance;
  }
  
  /**
   * Registers path and creation method for loadable class.
   *
   * @param string $class_name The name of class to register.
   * @param string $path Full path of include file.
   * @param string $method Method used for creation (default is __construct()).
   */
  public function registerLoadableClass($class_name, $path, $method = NULL) {
    
    if (is_file($path)) {
      include_once($path);
      $methods = get_class_methods($class_name);
      if (FALSE !== array_search($method, $methods)) {
        !isset($method) ? $method = '__construct' : NULL;
        $this->m_loadable_classes[$class_name] = array(
          self::CLASS_REG_PATH => $path,
          self::CLASS_REG_METHOD => $method,
        );
      }
      else {
        AblePolecat_Server::handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOTSTRAP_CLASS_REG,
          "Invalid registration for $class_name: constructor");
      }
    }
    else {
      AblePolecat_Server::handleCriticalError(ABLE_POLECAT_EXCEPTION_BOOT_PATH_INVALID,
        "Invalid include path for $class_name: include file path");
    }
  }
  
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
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$ClassRegistry)) {
      self::$ClassRegistry = new AblePolecat_ClassRegistry();
    }
    return self::$ClassRegistry;
  }
}