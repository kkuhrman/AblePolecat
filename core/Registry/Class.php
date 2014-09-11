<?php
/**
 * @file      polecat/core/Registry/Class.php
 * @brief     Encapsulates core database table [class].
 *
 * Handles registration and lazy loading of Able Polecat classes
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server', 'Paths.php')));

class AblePolecat_Registry_Class extends AblePolecat_RegistryAbstract {
  
  /**
   * Registry keys.
   */
  const KEY_ARTICLE_ID            = 'id';
  const KEY_CLASS_NAME            = 'className';
  const KEY_CLASS_FULL_PATH       = 'classFullPath';
  const KEY_CLASS_FACTORY_METHOD  = 'classFactoryMethod';
  const KEY_INTERFACE             = 'interface';
  
  /**
   * @var AblePolecat_Registry_Class Singleton instance.
   */
  private static $Registry = NULL;
  
  /**
   * @var Array Registry of classes which can be loaded.
   */
  private $Classes;
  
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
   * @return AblePolecat_Registry_Class Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Registry)) {
      try {
        self::$Registry = new AblePolecat_Registry_Class($Subject);
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
    // Class registration.
    //
    $this->Classes = array(
      self::KEY_ARTICLE_ID => array(),
      self::KEY_CLASS_NAME => array(),
      self::KEY_INTERFACE => array(),
    );
      
    //
    // Populate class from application database
    // Query application database for registered classes.
    //
    $sql = __SQL()->
      select('className', 'classId', 'classScope', 'isRequired', 'classFullPath', 'classFactoryMethod')->
      from('class');
    $Result = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
    if($Result->success()) {
      $Classes = $Result->value();
      $error_info = '';
      foreach($Classes as $key => $Class) {
        if (FALSE === $this->registerLoadableClass($Class['className'], $Class['classFullPath'], $Class['classFactoryMethod'], $error_info)) {
          $msg = sprintf("There is an invalid class definition in the database class registry for %s.",
            $Class['className']
          );
          $msg .= ' ' . $error_info;
        }
      }
    }
  }
  
  /**
   * Retrieve a list of classes corresponding to the given key name/value.
   *
   * @param string $keyName The name of a registry key.
   * @param string $keyValue Optional value of registry key.
   *
   * @return Array List of registered class names.
   */
  public function getClassListByKey($key, $value = NULL) {
    
    $ClassList = array();
    
    switch($key) {
      case self::KEY_ARTICLE_ID:
      case self::KEY_CLASS_NAME:
      case self::KEY_INTERFACE:
        if (isset($value)) {
          if (isset($this->Classes[$key][$value])) {
            $ClassList = $this->Classes[$key][$value];
          }
        }
        else {
          $ClassList = $this->Classes[$key];
        }
        break;

    }
    return $ClassList;
  }
  
  /**
   * Attempt to include class definition file and make it loadable.
   *
   * This function will determine location of include file from class name, assuming
   * class name follows standard core class library naming convention.
   *
   * This function assumes class name will follow proper naming and include file location 
   * convention (above) where name of class follows AblePolecat_Some_Class_Name and the 
   * include file is ./core/path/to/Some/Class/Name.php.
   *
   * @param $className Name of class to get include file path for.
   * @param $extension File extension.
   *
   * @return Array include file path and creation method, otherwise FALSE.
   */
  public function registerByConvention($className, $extension = 'php') {
    
    $response = FALSE;
    
    if (isset($this->Classes[self::KEY_CLASS_NAME][$className])) {
      $response = $this->Classes[self::KEY_CLASS_NAME][$className];
    }
    else {
      //
      // The relative path is contructed by trimming the file name (sans extension) from the end 
      // and the root directory name from the beginning of the class name. Underscores are then 
      // converted to directory separators.
      //
      $paths = explode('_', $className);
      $nesting_levels = count($paths) - 1;
      if ($nesting_levels >= 0) {
        $file_name = sprintf("%s.%s", array_pop($paths), $extension);
        $root_directory_name = array_shift($paths);
        $relative_path = implode(DIRECTORY_SEPARATOR, $paths);
        $default_directory_name = ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . $relative_path;
        $include_path = AblePolecat_Server_Paths::includeFile($file_name, $default_directory_name);
        if ($include_path) {
          //
          // Register class
          // @todo: the guessing of create method is very limited here.
          //
          $method = '__construct';
          if ($interfaces = class_implements($className)) {
            foreach($interfaces as $key => $interface) {
              switch ($interface) {
                default:
                  break;
                case 'AblePolecat_CacheObjectInterface';
                  $method = 'wakeup';
                  break;
              }
            }
          }
          $this->Classes[self::KEY_CLASS_NAME][$className] = array(
            self::KEY_CLASS_FULL_PATH => $include_path,
            self::KEY_CLASS_FACTORY_METHOD => $method,
          );
          $response = $this->Classes[self::KEY_CLASS_NAME][$className];
        }
      }
    }
    return $response;
  }
  
  /**
   * Check if class can be loaded in current environment.
   * 
   * @param string $className The name of class to check for.
   *
   * @return Array include file path and creation method, otherwise FALSE.
   */
  public function isLoadable($className) {
    
    $response = FALSE;
    if (isset($this->Classes[self::KEY_CLASS_NAME][$className])) {
      $response = $this->Classes[self::KEY_CLASS_NAME][$className];
    }
    else {
      $response = $this->registerByConvention($className);
    }
    return $response;
  }
  
  /**
   * Get instance of given class.
   *
   * @param string $className The name of class to instantiate.
   * @param mixed $param Zero or more optional parameters to be passed to creational method.
   * 
   * @return object Instance of given class or NULL.
   */
  public function loadClass($className, $param = NULL) {
    
    $Instance = NULL;
    $info = $this->isLoadable($className);
    if (isset($info[self::KEY_CLASS_FACTORY_METHOD])) {
      //
      // Get any parameters passed.
      //
      $parameters = array();
      if (isset($param)) {
        $args = func_get_args();
        array_shift($args);
        $parameters = $args;
      }
      switch ($info[self::KEY_CLASS_FACTORY_METHOD]) {
        default:
          $Instance = call_user_func_array(array($className, $info[self::KEY_CLASS_FACTORY_METHOD]), $parameters);
          break;
        case '__construct':
          $Instance = new $className;
          break;
      }
    }
    return $Instance;
  }
  
  /**
   * Registers path and creation method for loadable class.
   *
   * @param string $className The name of class to register.
   * @param string $path Full path of include file if not given elsewhere in script.
   * @param string $method Method used for creation (default is __construct()).
   * @param string &$error_info If passed any error info is stored here.
   *
   * @return TRUE if class is registered, otherwise FALSE.
   */
  public function registerLoadableClass($className, $path = NULL, $method = NULL, &$error_info = NULL) {
    
    $registered = FALSE;
    
    if (!isset($path)) {
      //
      // Attempt to define path based on core class naming convention.
      //
      $path = str_replace(array('AblePolecat', '_'), array(ABLE_POLECAT_CORE, DIRECTORY_SEPARATOR), $className);
      $path .= '.php';
    }
    if (is_file($path)) {
      include_once($path);
    
      //
      // If a creational (factory) method is not provided, assume use of default constructor.
      //
      if (!isset($method)) {
        $method = '__construct';
      }
      else if ($method != '__construct') {
      
        $methods = get_class_methods($className);
        isset($methods) ? $methods = array_flip($methods) : NULL;
        if (!isset($methods[$method])) {
          $error_info .= "Class factory method $className::$method does not exist.";
          throw new AblePolecat_Registry_Exception("Class factory method $className::$method does not exist.");
        }
      }
      
      //
      // Registry
      //
      $this->Classes[self::KEY_CLASS_NAME][$className] = array(
        self::KEY_CLASS_FULL_PATH => $path,
        self::KEY_CLASS_FACTORY_METHOD => $method,
      );
      
      //
      // Interfaces implemented by class.
      //
      $interfaces = class_implements($className, FALSE);
      array_key_exists('AblePolecat_AccessControl_ArticleInterface', $interfaces) ? $Id = $className::getId() : $Id = NULL;;
      foreach($interfaces as $interfaceName) {
        //
        // Map by interface name.
        //
        if (!isset($this->Classes[self::KEY_INTERFACE][$interfaceName])) {
          $this->Classes[self::KEY_INTERFACE][$interfaceName] = array();
        }
        if (!isset($this->Classes[self::KEY_INTERFACE][$interfaceName][$className])) {
          $this->Classes[self::KEY_INTERFACE][$interfaceName][$className] = array();
        }
        $this->Classes[self::KEY_INTERFACE][$interfaceName][$className][self::KEY_CLASS_NAME] = $className;
        if (isset($Id)) {
          $this->Classes[self::KEY_INTERFACE][$interfaceName][$className][self::KEY_ARTICLE_ID] = $Id;
        }
        $registered = TRUE;
      }
    }
    else if (isset($error_info)) {      
      $error_info .= "Invalid include path ($path)";
    }
    
    return $registered;
  }
}
