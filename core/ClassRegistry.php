<?php
/**
 * @file: ClassRegistry.php
 * Handles registration and lazy loading of Able Polecat classes.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Server', 'Paths.php')));

interface AblePolecat_ClassRegistryInterface {
  
  /**
   * Check if class can be loaded in current environment.
   * 
   * @param string $class_name The name of class to check for.
   *
   * @return mixed Full include path if class can be loaded, otherwise FALSE.
   */
  public function isLoadable($class_name);
  
  /**
   * Get instance of given class.
   *
   * @param string $class_name The name of class to instantiate.
   * 
   * @return object Instance of given class or NULL.
   */
  public function loadClass($class_name);
  
  /**
   * Registers path and creation method for loadable class.
   *
   * @param string $class_name The name of class to register.
   * @param string $path Full path of include file.
   * @param string $method Method used for creation (default is __construct()).
   */
  public function registerLoadableClass($class_name, $path, $method = NULL);
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep();
  
  /**
   * Creational function; restores saved state from memory/storage.
   *
   * @return object Instance of class implmenting AblePolecat_ClassRegistryInterface.
   */
  public static function wakeup();
}

class AblePolecat_ClassRegistry implements AblePolecat_ClassRegistryInterface {
  
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
   * Persist state prior to going out of scope.
   */
  public function sleep() {
    self::$ClassRegistry = NULL;
  }
  
  /**
   * Creational function; restores saved state from memory/storage.
   *
   * @return object Instance of class implmenting AblePolecat_ClassRegistryInterface.
   */
  public static function wakeup() {
    if (!isset(self::$ClassRegistry)) {
      self::$ClassRegistry = new AblePolecat_ClassRegistry();
    }
    return self::$ClassRegistry;
  }
  
  final protected function __construct() {
    $this->initialize();
  }
  
  final public function __destruct() {
    $this->sleep();
  }
}