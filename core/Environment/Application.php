<?php
/**
 * @file: Application.php
 * Environment for Able Polecat Application Mode.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Environment.php');

class AblePolecat_Environment_Application extends AblePolecat_EnvironmentAbstract {
  
  /**
   * @var AblePolecat_Environment_Server Singleton instance.
   */
  private static $Environment = NULL;
  
  /**
   * @var Array Registry of contributed module configurations.
   */
  private $m_registered_modules;
  
  /**
   * Extends __construct(). 
   * 
   * Sub-classes can override to initialize members prior to load.
   */
  protected function initialize() {
    parent::initialize();
    $this->m_registered_modules = array();
    
    //
    // Needed for module registration.
    //
    AblePolecat_Server::getClassRegistry()->registerLoadableClass(
        'AblePolecat_Conf_Module',
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Conf', 'Module.php')),
        'touch'
      );
  }
  
  /**
   * Helper function searches given directory for module configuration file.
   *
   * Able Polecat requires contributed modules to have at least one configuration file 
   * with the name module.xml. This file *must* be located in the mods directory. All 
   * other module resources (files, class libraries, etc) can be located elsewhere as 
   * defined in module.xml.
   *
   * Examples of acceptable module configuration file placement:
   * 1. One configuration for all runtime contexts:
   *    [AblePolecat ROOT]/mods/MyModule/conf/module.xml
   * 2. Different configurations for one or more runtime contexts:
   *    [AblePolecat ROOT]/mods/MyModule/conf/dev/module.xml
   *                                      .../qa/module.xml
   *                                      .../use/module.xml
   *
   * @param string $search_directory Name of directory to search.
   * @param string $parent_directory i.e. cd ..
   *
   * @return string Full path name of module configuration file or NULL if not found.
   */
  protected function findModuleConfigurationFile($search_directory, $parent_directory = ABLE_POLECAT_MODS_PATH) {
    
    $conf_path = NULL;
    
    if ($search_directory != "." && $search_directory != "..") {
      $full_path = $parent_directory . DIRECTORY_SEPARATOR . $search_directory;
      if (is_dir($full_path)) {
        $test_path = implode(DIRECTORY_SEPARATOR, array($full_path, 'conf', 'module.xml'));
        if (file_exists($test_path)) {
          //
          // One configuration for all runtime contexts.
          //
          $conf_path = $test_path;
        }
        else {
          $context_dir = AblePolecat_Conf_Server::getDefaultSubDir();
          $test_path = implode(DIRECTORY_SEPARATOR, array($full_path, 'conf', $context_dir, 'module.xml'));
          if (file_exists($test_path)) {
            //
            // Configuration specific to runtime context.
            //
            $conf_path = $test_path;
          }
        }
      }
    }
    return $conf_path;
  }
  
  /**
   * Initialize the environment for Able Polecat.
   *
   * @return AblePolecat_Environment_Server.
   */
  public static function load() {
    $Environment = self::$Environment;
    if (!isset($Environment)) {
      //
      // Create environment object.
      //
      $Environment = new AblePolecat_Environment_Application();
      
      //
      // Initialize access control for application environment settings.
      //
      $Agent = $Environment->loadAccessControlAgent(
        'AblePolecat_AccessControl_Agent_Application',
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Agent', 'Application.php')),
        'load'
      );
      
      //
      // Register and load contributed classes
      //
      $Environment->registerModules();
      // $Environment->loadModules();
      
      //
      // Initialize singleton instance.
      //
      self::$Environment = $Environment;
    }
    return self::$Environment;
  }
  
  /**
   * Return configuration file for a registered module.
   * 
   * @param string $moduleName Name of a registered module.
   * @param string $start Optional offset to start reading from.
   * @param string $end Optional offset to end reading at.
   *
   * @return SimpleXMLElement Environment configuration settings.
   */
  public function getModuleConf($moduleName, $start = NULL, $end = NULL) {
    
    $modConf = NULL;
    if (isset($this->Agent) && isset($this->m_registered_modules[$moduleName])) {
      if (isset($this->m_registered_modules[$moduleName]['conf'])) {
        $modConf = $this->m_registered_modules[$moduleName]['conf']->
          read($this->Agent, $start, $end);
      }
    }
    return $modConf;
  }
  
  /**
   * Return module registration data.
   *
   * @return Array.
   */
  public function getRegisteredModules() {
    return $this->m_registered_modules;
  }
  
  /**
   * Registers all contributed modules in mods directory flagged to be registered.
   * @see findModuleConfigurationFile().
   */
  public function registerModules() {
    //
    // Application agent must be assigned already or all shall fail... oh woe!
    //
    if (isset($this->Agent)) {
      if (count($this->m_registered_modules) === 0) {
        // @todo: won't get here if these paths don't exists
        if (file_exists(ABLE_POLECAT_MODS_PATH) && is_dir(ABLE_POLECAT_MODS_PATH)) {
          $h_mods_dir = opendir(ABLE_POLECAT_MODS_PATH);
          if ($h_mods_dir) {
            while (false !== ($current_file = readdir($h_mods_dir))) {
              $module_conf_path = $this->findModuleConfigurationFile($current_file);
              if (isset($module_conf_path)) {
                $ModConfig = AblePolecat_Server::getClassRegistry()->loadClass('AblePolecat_Conf_Module');
                if (isset($ModConfig)) {
                  //
                  // Grant open permission on config file to agent.
                  //
                  $ModConfig->setPermission($this->Agent, AblePolecat_AccessControl_Constraint_Open::getId());
                  $ModConfig->setPermission($this->Agent, AblePolecat_AccessControl_Constraint_Read::getId());              
                  $ModConfigUrl = AblePolecat_AccessControl_Resource_Locater::create($module_conf_path);
                  $this->registerModule($ModConfig, $ModConfigUrl);
                }
              }
            }
            closedir($h_mods_dir);
          }
        }
        else {
          throw new AblePolecat_Environment_Exception(ABLE_POLECAT_EXCEPTION_MSG(ABLE_POLECAT_EXCEPTION_MODS_PATH_INVALID), 
            ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION);
        }
      }
    }
    else {
      throw new AblePolecat_Environment_Exception('Cannot register modules before application agent is assigned.', 
        ABLE_POLECAT_EXCEPTION_BOOT_SEQ_VIOLATION);
    }
  }
  
  /**
   * Registers the given contributed module.
   *
   * Configuration is type SimpleXMLElement, elements are SimpleXMLElement or SimpleXMLIterator.
   * Must cast text as string when passing as function parameters. Make sure __toString is invoked.
   * @see: http://us3.php.net/manual/en/simplexml.examples-basic.php
   *
   * @param AblePolecat_Conf_Module $modConfig Module configuration file
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $modPath Full path to contributed module directory.
   */
  public function registerModule(AblePolecat_Conf_Module $modConfig, AblePolecat_AccessControl_Resource_LocaterInterface $modPath) {
    
    if ($modConfig->open($this->Agent, $modPath)) {
      $modConfSxElement = $modConfig->read($this->Agent);
      $moduleAttributes = $modConfSxElement->attributes();
      isset($modConfSxElement->classes) ? $moduleClasses = $modConfSxElement->classes : $moduleClasses = array();
      $modLoadClasses = array();
      foreach($moduleClasses as $key => $class) {
        if(isset($class->{'class'})) {
          $classAttributes = $class->{'class'}->attributes();
          if (isset($classAttributes['register']) && intval($classAttributes['register'])) {
            isset($class->{'class'}->classname) ? $className = $class->{'class'}->classname->__toString() : $className = NULL;
            isset($class->{'class'}->filename) ? $fileName = $class->{'class'}->filename->__toString() : $fileName = NULL;
            if(isset($className) && isset($fileName)) {
              //
              // Trim any leading and trailing slashes from relative URL.
              //
              isset($moduleAttributes['fullpath']) ? $moduleFullpath = trim($moduleAttributes['fullpath'], '/') : $moduleFullpath = '';
              isset($fileName) ? $classFullPath = $moduleFullpath . DIRECTORY_SEPARATOR . $fileName : $classFullPath = NULL;
              isset($class->{'class'}->classFactoryMethod) ? $classFactoryMethod = $class->{'class'}->classFactoryMethod->__toString() : $classFactoryMethod = NULL;
              if(isset($classFullPath) && isset($classFactoryMethod)) {
                AblePolecat_Server::getClassRegistry()->registerLoadableClass($className, $classFullPath, $classFactoryMethod);
                if (isset($classAttributes['load']) && intval($classAttributes['load'])) {
                  $modLoadClasses[] = $className;
                }
              }
            }
          }
        }
      }
      $moduleName = $moduleAttributes['name']->__toString();
      $this->m_registered_modules[$moduleName] = array(
        'conf' => $modConfig,
        'path' => $modPath,
        'classes' => $modLoadClasses,
      );
      AblePolecat_Server::log(AblePolecat_LogInterface::STATUS, 
        "Registered contributed module $moduleName.");
    }
    else {
      $path = $modPath->__toString();
      AblePolecat_Server::log(AblePolecat_LogInterface::ERROR, 
        "Failed to open module configuration file at $path.");
    }
  }
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep() {}
}