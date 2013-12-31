<?php
/**
 * @file: Registry/Class.php
 * Handles registration and lazy loading of Able Polecat classes.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server', 'Paths.php')));

class AblePolecat_Registry_Class extends AblePolecat_RegistryAbstract {
  
    /**
   * Class registration constants.
   */
  const CLASS_REG_PATH    = 'classFullPath';
  const CLASS_REG_METHOD  = 'classFactoryMethod';
  
  /**
   * @var AblePolecat_Registry_Class Singleton instance.
   */
  private static $Registry = NULL;
  
  /**
   * @var Array Registry of classes which can be loaded.
   */
  private $Classes;
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    
    //
    // Class registration.
    //
    $this->Classes = array();
    
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
    
    if (0 == count($Classes)) {
      throw new AblePolecat_Registry_Exception(
        'There are no class definitions saved in the database class registry.', 
        AblePolecat_Error::BOOTSTRAP_CLASS_REG
      );
    }
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
    if (isset($this->Classes[$className])) {
      $response = $this->Classes[$className];
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
          $Instance = call_user_func_array(array($className, $info[self::CLASS_REG_METHOD]), $parameters);
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
    }
    else if (isset($error_info)) {      
      $error_info .= "Invalid include path ($path)";
    }
    
    $methods = get_class_methods($className);
    if (isset($methods)) {
      if (FALSE !== array_search($method, $methods)) {
        !isset($method) ? $method = '__construct' : NULL;
        $this->Classes[$className] = array(
          self::CLASS_REG_PATH => $path,
          self::CLASS_REG_METHOD => $method,
        );
        $registered = TRUE;
      }
      else {
        $error_info .= "Invalid create method ($method)";
      }
    }
    return $registered;
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
}
