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
  private $registeredClasses;
  
  /**
   * @var Array Registered contributed modules.
   */
  private $registeredModules;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    //
    // Class registration.
    //
    $this->registeredClasses = array();
    
    //
    // Module registration.
    // 'conf' contains all class conf data in order of registration.
    // 'interface' registers classes by type of implemented interface
    // 'module' registers classes by module name
    // last two arrays are keyed as follows: class name => index in 'conf'[]
    //
    $this->registeredModules = array(
      'conf' => array(),
      'interface' => array(),
      'module' => array(),
    );
    $AblePolecatInterfaces = self::getAblePolecatInterfaces();
    foreach($AblePolecatInterfaces as $key => $InterfaceName) {
      $this->registeredModules['interface'][$InterfaceName] = array();
    }
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
   * Returns list of names of registered module classes.
   *
   * @param $filter_name Preset name of a module registry filter (interface name, module name etc).
   * @param $filter_value Value of filter.
   *
   * If no filter is defined, all registered module class names will be returned.
   *
   * @return Array Names of registered module classes.
   */
  public function getModuleClasses($filter_name = NULL, $filter_value = NULL) {
    
    $moduleClasses = array();
    $searchReg = NULL;
    switch($filter_name) {
      default:
        foreach($this->registeredModules['conf'] as $className => $classConf) {
          $moduleClasses[] = $className;
        }
        break;
      case 'module':
      case 'interface':      
        if (isset($filter_value) && isset($this->registeredModules[$filter_name][$filter_value])) {
          $moduleClasses = $this->registeredModules[$filter_name][$filter_value];
        }
        else {
          $moduleClasses = $this->registeredModules[$filter_name];
        }
        break;
    }    
    return $moduleClasses;
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
    if (isset($this->registeredClasses[$class_name])) {
      $response = $this->registeredClasses[$class_name];
    }
    return $response;
  }
  
  /**
   * Get instance of given class.
   *
   * @param string $class_name The name of class to instantiate.
   * @param mixed $param Zero or more optional parameters to be passed to creational method.
   * 
   * @return object Instance of given class or NULL.
   */
  public function loadClass($class_name, $param = NULL) {
    
    $Instance = NULL;
    $info = $this->isLoadable($class_name);
    if (isset($info[self::CLASS_REG_METHOD])) {
      //
      // Get any parameters passed.
      //
      $parameters = array();
      if (isset($param)) {
        $args = func_get_args();
        array_shift($args);
        $parameters = $args;
      }
      switch ($info[self::CLASS_REG_METHOD]) {
        default:
          $Instance = call_user_func_array(array($class_name, $info[self::CLASS_REG_METHOD]), $parameters);
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
   *
   * @return TRUE if class is registered, otherwise FALSE.
   */
  public function registerLoadableClass($class_name, $path = NULL, $method = NULL) {
    
    $registered = FALSE;
    
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
        $this->registeredClasses[$class_name] = array(
          self::CLASS_REG_PATH => $path,
          self::CLASS_REG_METHOD => $method,
        );
        $registered = TRUE;
      }
      else {
        AblePolecat_Server::handleCriticalError(AblePolecat_Error::BOOTSTRAP_CLASS_REG,
          "Invalid registration for $class_name: constructor");
      }
    }
    return $registered;
  }
  
  /**
   * Register classes in contributed modules.
   *
   * @param string moduleName Name of module.
   * @param Array $classConfig Class configuration data from conf file.
   *
   * @see AblePolecat_Conf_Module::getModuleClasses()
   */
  public function registerModuleClass($moduleName, $classConfig) {
  
    $registerClass = isset($classConfig['attributes']) &&
      isset($classConfig['attributes']['register']) &&
      (0 != $classConfig['attributes']['register']);
    
    if ($registerClass) {
      isset($classConfig['interface']) ? $interface = $classConfig['interface'] : $interface = NULL;
      isset($classConfig['classname']) ? $className = $classConfig['classname'] : $className = NULL;
      isset($classConfig['fullpath']) ? $fullPath = $classConfig['fullpath'] : $fullPath = NULL;
      isset($classConfig['classFactoryMethod']) ? $classFactoryMethod = $classConfig['classFactoryMethod'] : $classFactoryMethod = NULL;
      
      if (isset($interface) && isset($className) && isset($fullPath) && isset($classFactoryMethod) && isset($this->registeredModules['interface'][$interface])) {
        
        //
        // UUID is vital for many interface implementations such as service clients; but optional for some others.
        //
        isset($classConfig['attributes']['id']) ? $classId = $classConfig['attributes']['id'] : $classId = $className;
        
        if ($this->registerLoadableClass($className, $fullPath, $classFactoryMethod)) {
          if (!isset($this->registeredModules['module'][$moduleName])) {
            $this->registeredModules['module'][$moduleName] = array();
          }
          $this->registeredModules['conf'][$className] = $classConfig;
          if (!isset($this->registeredModules['interface'][$interface][$moduleName])) {
            $this->registeredModules['interface'][$interface][$moduleName] = array();
          }
          $this->registeredModules['interface'][$interface][$moduleName][$classId] = $className;
          if (!isset($this->registeredModules['module'][$moduleName][$interface])) {
            $this->registeredModules['module'][$moduleName][$interface] = array();
          }
          $this->registeredModules['module'][$moduleName][$interface][$classId] = $className;
        }
        else {
          $registerClass = FALSE;
        }
      }
      else {
        AblePolecat_Server::log('warning', "Invalid class configuration encountered in module $moduleName conf file.");
        $registerClass = FALSE;
      }
    }
    return $registerClass;
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