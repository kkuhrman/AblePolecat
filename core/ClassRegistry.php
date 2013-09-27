<?php
/**
 * @file: ClassRegistry.php
 * Handles registration and lazy loading of Able Polecat classes.
 */

//
// One of the few core files which does not make use of the defined constant
// 'ABLE_POLECAT_PATH', which is initialized in the first required script.
//
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
   * @var List of Able Polecat interfaces.
   */
  private static $AblePolecatInterfaces = NULL;
   
  
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
   * Return names of Able Polecat interfaces.
   *
   * @return Array Names of Able Polecat interfaces.
   */
  public static function getAblePolecatInterfaces() {
    if (!isset(self::$AblePolecatInterfaces)) {
      self::$AblePolecatInterfaces = array(
        'AblePolecat_AccessControl_AgentInterface',
        'AblePolecat_AccessControl_ArticleInterface',
        'AblePolecat_AccessControl_ConstraintInterface',
        'AblePolecat_AccessControl_Resource_LocaterInterface',
        'AblePolecat_AccessControl_ResourceInterface',
        'AblePolecat_AccessControl_RoleInterface',
        'AblePolecat_AccessControl_SubjectInterface',
        'AblePolecat_CacheObjectInterface',
        'AblePolecat_Http_Message_RequestInterface',
        'AblePolecat_Http_MessageInterface',
        'AblePolecat_HttpInterface',
        'AblePolecat_LogInterface',
        'AblePolecat_MessageInterface',
        'AblePolecat_ModeInterface',
        'AblePolecat_Server_CheckInterface',
        'AblePolecat_Service_ClientInterface',
        'AblePolecat_Service_DtxInterface',
        'AblePolecat_Service_InitiatorInterface',
        'AblePolecat_Service_Interface',
        'AblePolecat_SessionInterface',
        'AblePolecat_TransactionInterface',
      );
    }
    return self::$AblePolecatInterfaces;
  }
  
  /**
   * Returns name of Able Polecat interface if implemented.
   *
   * @param mixed $class Object or class name.
   * @param string $path Full path of include file if not registered.
   *
   * @return mixed Name of implemented Able Polecat interface(s) or false.
   */
  public static function getImplementedAblePolecatInterfaces($class, $path = NULL) {
    
    $implemented_interface = FALSE;
    
    //
    // Class may not be regisetered
    //
    if (isset($path) && is_file($path)) {
      include_once($path);
    }
    
    if (isset($class)) {
      $class_name = FALSE;
      if (is_object($class)) {
        $class_name = get_class($class);
      }
      else if (is_string($class)) {
        $class_name = $class;
      }
      if ($class_name) {        
        $AblePolecatInterfaces = self::getAblePolecatInterfaces();
        foreach($AblePolecatInterfaces as $key => $InterfaceName) {
          if (is_subclass_of($class_name, $InterfaceName)) {
            if ($implemented_interface === FALSE) {
              $implemented_interface = array();
            }
            $implemented_interface[] = $InterfaceName;
          }
        }
      }
    }    
    return $implemented_interface;
  }
  
  /**
   * Check if given class implements given Able Polecat interface.
   *
   * @param string $interface Name of an Able Polecat interface.
   * @param mixed $class Object or class name.
   * @param string $path Full path of include file if not registered.
   *
   * @return bool TRUE if $interface is an Able Polecat interface and is implemented by $class, otherwise FALSE.
   */
  public static function implementsAblePolecatInterface($interface, $class, $path = NULL) {
    
    $result = FALSE;
    $implemented_interfaces = array_flip(self::getImplementedAblePolecatInterfaces($class, $path));
    if ($implemented_interfaces) {
      $result = array_key_exists($interface, $implemented_interfaces);
    }
    return $result;
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
   * @param string $path Full path of include file if not given elsewhere in script.
   * @param string $method Method used for creation (default is __construct()).
   */
  public function registerLoadableClass($class_name, $path = NULL, $method = NULL) {
    
    if (isset($path)) {
      if (is_file($path)) {
        include_once($path);
      }
      else {
        AblePolecat_Server::handleCriticalError(AblePolecat_Error::BOOT_PATH_INVALID,
          "Invalid include path for $class_name: $path");
      }
    }
    
    $methods = get_class_methods($class_name);
    if (isset($methods)) {
      if (FALSE !== array_search($method, $methods)) {
        !isset($method) ? $method = '__construct' : NULL;
        $this->m_loadable_classes[$class_name] = array(
          self::CLASS_REG_PATH => $path,
          self::CLASS_REG_METHOD => $method,
        );
      }
      else {
        AblePolecat_Server::handleCriticalError(AblePolecat_Error::BOOTSTRAP_CLASS_REG,
          "Invalid registration for $class_name: constructor");
      }
    }
  }
  
  /**
   * Register classes in contributed modules.
   *
   * @param string $className Name of class.
   * @param string $classFullPath FullPath of class.
   * @param string $classFactoryMethod FactoryMethod of class.
   * @param string $classInterface Interface of class.
   */
  public function registerModuleClass($className, $filePath, $classFactoryMethod,$interface) {
    
    $implemented_interfaces = self::getImplementedAblePolecatInterfaces($className, $filePath, $interface);
    if ($implemented_interfaces) {
      //
      // Do not pass $path because getImplementedAblePolecatInterfaces() has already included file.
      //
      $this->registerLoadableClass($className, NULL, $classFactoryMethod);
      
      //
      // @todo: now what?
      // if it's a resource such as a logger, register with app mode
      // if it's a service client, register it with bus
      // and so on...
      //
      if(AblePolecat_Mode_Application::isValidResource($interface, $className)) {
        AblePolecat_Server::log('warning', '@todo: register resources and repeat for service clients.');
      }
      
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