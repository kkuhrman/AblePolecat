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
   * Evaluates the setting of the <module register> attribute in the given conf file.
   * 
   * AblePolecat_Environment_Application implements a registry of contributed code 
   * (modules), which is stored in $m_registered_modules. This registry contains 
   * information such as full path of module, classes, libraries and more.
   * If <module register="0">, this registration will be skipped completely.
   * If <module register> is non-zero, the module will be registered and, depending
   * on the assigned value, may be registered as an application resource, service, 
   * service client etc.
   *
   * @param AblePolecat_Conf_Module $modConfig Module configuration file
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $modPath Full path to contributed module directory.
   *
   * @return mixed Module registration directive or FALSE if <module register="0">
   */
  protected function getModuleRegisterDirective(AblePolecat_Conf_Module $modConfig, AblePolecat_AccessControl_Resource_LocaterInterface $modPath) {
    
    $mod_register_directive = FALSE;
    
    $modConfig->open($this->Agent, $modPath);
    $modConfSxElement = $modConfig->read($this->Agent);
    $moduleAttributes = $modConfSxElement->attributes();
    if (isset($moduleAttributes['register'])) {
      switch($moduleAttributes['register']) {
        default:
          //
          // log a warning
          //
          AblePolecat_Server::log('warning', sprintf("Error in conf file %s. Unsupported module registration directive %s",
            $modPath,
            $moduleAttributes['register']));
          break;
        case '0';
          break;
        case 'resource':
        case 'service':
        case 'client':
          $mod_register_directive = $moduleAttributes['register']->__toString();
          break;
      }
    }
    return $mod_register_directive;
  }
  
  /**
   * Registers all contributed modules in mods directory flagged to be registered.
   *
   * Module conf files have two attributes which determine whether class(es) in 
   * contributed modules should be added to class registry and loaded at bootstrap
   * or on demand. The attributes are 'register' and 'load' and are defined in both
   * the <module> and <class> elements.
   *
   * registerModule() also adds any classes defined in module conf file to the Able Polecat
   * class registry if <class register="[non-zero]">.
   *
   * <module load> is not evaluated at present. It is reserved for future use.
   *
   * <class load> is evaluated by certain functions to determine whether class is 
   * instantiated at bootstrap or later on demand.
   *
   * @see findModuleConfigurationFile(), registerModule().
   */
  protected function registerModules() {
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
                  
                  //
                  // Process module conf file if <module register> is set to a supported registration directive.
                  //
                  $mod_register_directive = $this->getModuleRegisterDirective($ModConfig, $ModConfigUrl);
                  if ($mod_register_directive) {
                    $this->registerModule($ModConfig, $ModConfigUrl);
                  }
                }
              }
            }
            closedir($h_mods_dir);
          }
        }
        else {
          throw new AblePolecat_Environment_Exception(AblePolecat_Error::defaultMessage(AblePolecat_Error::MODS_PATH_INVALID), 
            AblePolecat_Error::BOOT_SEQ_VIOLATION);
        }
      }
    }
    else {
      throw new AblePolecat_Environment_Exception('Cannot register modules before application agent is assigned.', 
        AblePolecat_Error::BOOT_SEQ_VIOLATION);
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
   * @param string $registration_directive If provided will skip redundant call to getModuleRegisterDirective().
   */
  protected function registerModule(AblePolecat_Conf_Module $modConfig, 
    AblePolecat_AccessControl_Resource_LocaterInterface $modPath,
    $registration_directive = NULL) {
    
    if (!isset($registration_directive)) {
      $registration_directive = $this->getModuleRegisterDirective($modConfig, $modPath);
    }
    if ($registration_directive && $modConfig->open($this->Agent, $modPath)) {
      // $modConfSxElement = $modConfig->read($this->Agent);
      // $moduleAttributes = $modConfSxElement->attributes();
      // var_dump($moduleAttributes);
      // var_dump($modConfig->getModuleAttributes($this->Agent));
      $moduleAttributes = $modConfig->getModuleAttributes($this->Agent);
      $moduleFullpath = trim($moduleAttributes['fullpath'], '/');
      $moduleClasses = $modConfig->getModuleClasses($this->Agent);
      foreach($moduleClasses as $className => $classConfig) {
        if ($classConfig[AblePolecat_Conf_Module::ELEMENT_ATTR][AblePolecat_Conf_Module::ATTRIBUTE_REG]) {
          $classFullPath = $moduleFullpath . DIRECTORY_SEPARATOR . $classConfig[AblePolecat_Conf_Module::ELEMENT_FILENAME];
          $classFactoryMethod = $classConfig[AblePolecat_Conf_Module::ELEMENT_CLASSMETH];
          $classInterface = $classConfig[AblePolecat_Conf_Module::ELEMENT_INTERFACE];
          AblePolecat_Server::getClassRegistry()->registerModuleClass($className, $classFullPath, $classFactoryMethod, $classInterface);
        }
      }
      if (true) return;
      
      //
      // @todo: move all this module class business to AblePolecat_ClassRegistry::registerModuleClasses()
      //
      isset($modConfSxElement->classes) ? $moduleClasses = $modConfSxElement->classes : $moduleClasses = array();
      $modLoadClasses = array();
      $modNoLoadClasses = array();
      foreach($moduleClasses as $key => $class) {
        if(isset($class->{'class'})) {
          $classAttributes = $class->{'class'}->attributes();
          if (isset($classAttributes['register']) && intval($classAttributes['register'])) {
            isset($class->{'class'}->classname) ? $className = $class->{'class'}->classname->__toString() : $className = NULL;
            isset($class->{'class'}->interface) ? $interface = $class->{'class'}->interface->__toString() : $interface = NULL;
            isset($class->{'class'}->filename) ? $fileName = $class->{'class'}->filename->__toString() : $fileName = NULL;
            if(isset($className) && isset($interface) && isset($fileName)) {
              //
              // Trim any leading and trailing slashes from relative URL.
              //
              isset($moduleAttributes['fullpath']) ? $moduleFullpath = trim($moduleAttributes['fullpath'], '/') : $moduleFullpath = '';
              isset($fileName) ? $classFullPath = $moduleFullpath . DIRECTORY_SEPARATOR . $fileName : $classFullPath = NULL;
              isset($class->{'class'}->classFactoryMethod) ? $classFactoryMethod = $class->{'class'}->classFactoryMethod->__toString() : $classFactoryMethod = NULL;
              if(isset($classFullPath) && isset($classFactoryMethod)) {
                //
                // *All* module classes will be registered by default unless the class attribute 'register'
                // is set to zero in the conf file (e.g. <class register="0">). Generally, classes should be
                // preregistered unless they are somehow included internally by the module only on-demand.
                //
                if (isset($classAttributes['register']) && intval($classAttributes['register']) === 0) {
                  //
                  // do not register
                  //
                  continue;
                }
                AblePolecat_Server::getClassRegistry()->registerModuleClass($className, $classFullPath, $classFactoryMethod);
                
                // var_dump(AblePolecat_Server::getClassRegistry()->implementsAblePolecatInterface('AblePolecat_Service_ClientInterface', $className, $classFullPath));
                
                //
                // Classes will not automatically be instantiated until needed unless the class attribute 'load'
                // is explicitly non-zero in the conf file (e.g. <class load="1">). Load at boot time may be useful
                // for some classes such as loggers; others should be on-demand only.
                //
                if (isset($classAttributes['load']) && intval($classAttributes['load']) != 0) {
                  $modLoadClasses[] = $className;
                }
                else {
                  $modNoLoadClasses[] = $className;
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
        'classes' => array('load' => $modLoadClasses, 'no load' => $modNoLoadClasses,),
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
   * Return full path of given module.
   * 
   * @param string $moduleName Name of a registered module.
   *
   * @return mixed Full path or FALSE.
   */
  public function getModulePath($moduleName) {
    
    $modPath = FALSE;
    if (isset($this->Agent) && isset($this->m_registered_modules[$moduleName])) {
      isset($this->m_registered_modules[$moduleName]['path']) ? $modPath = $this->m_registered_modules[$moduleName]['path'] : NULL;
    }
    return $modPath;
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
   * @return AblePolecat_Environment_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
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
        'wakeup'
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
}