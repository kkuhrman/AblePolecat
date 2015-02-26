<?php
/**
 * @file      polecat/core/Registry/Class.php
 * @brief     Encapsulates core database table [class].
 *
 * Handles registration and lazy loading of Able Polecat classes
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Cached.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xhtml', 'Tpl.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xml.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Class.php')));

interface AblePolecat_Registry_ClassInterface extends AblePolecat_RegistryInterface {
  /**
   * Registry keys.
   */
  const KEY_INTERFACE   = 'interface';
  
  /**
   * Return a list of all classes implementing given interface.
   *
   * @param string $interfaceName
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public function getInterfaceImplementations($interfaceName);
}

class AblePolecat_Registry_Class 
  extends AblePolecat_RegistryAbstract 
  implements AblePolecat_Registry_ClassInterface {
    
  /**
   * AblePolecat_AccessControl_Article_StaticInterface
   */
  const UUID = '7b70a499-b7b0-11e4-a12d-0050569e00a2';
  const NAME = __CLASS__;
  
  /**
   * @var AblePolecat_Registry_Class Singleton instance.
   */
  private static $Registry = NULL;
  
  /**
   * @var Array[AblePolecat_Registry_EntryInterface].
   */
  private $InterfaceImplementations;
  
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
   * Return Common name.
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
   * @return AblePolecat_Registry_Class Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Registry)) {
      //
      // Create instance of singleton.
      //
      self::$Registry = new AblePolecat_Registry_Class($Subject);
      self::$Registry->registerCoreClasses();
      
      if (AblePolecat_Database_Pdo::ready()) {
        //
        // Get project database.
        //
        $CoreDatabase = AblePolecat_Database_Pdo::wakeup($Subject);
        
        //
        // Load [lib]
        //
        $sql = __SQL()->
          select('id', 'name', 'classLibraryId', 'classFullPath', 'classFactoryMethod', 'lastModifiedTime')->
          from('class');
        $QueryResult = $CoreDatabase->query($sql);
        foreach($QueryResult as $key => $Class) {
          $RegistryEntry = AblePolecat_Registry_Entry_Class::create($Class);
          self::$Registry->addRegistration($RegistryEntry);
        }
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Class registry initialized.');
      }
    }
    return self::$Registry;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Database_InstallerInterface.
   ********************************************************************************/
   
  /**
   * Install class registry on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public static function install(AblePolecat_DatabaseInterface $Database) {
    //
    // Load class library registry.
    //
    $ClassLibraryRegistry = AblePolecat_Registry_ClassLibrary::wakeup();
    
    //
    // Core class library conf file.
    //
    $coreFile = AblePolecat_Mode_Config::getCoreClassLibraryConfFile();
    
    //
    // Get package (core class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($coreFile, 'package');
    $corePackageNode = $Nodes->item(0);
    if (!isset($corePackageNode)) {
      $message = 'core class library configuration file must contain a package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    //
    // Create DML statements for classes.
    //
    $coreClassLibraryId = $corePackageNode->getAttribute('id');
    $ClassLibraryRegistration = $ClassLibraryRegistry->getRegistrationById($coreClassLibraryId);
    if (isset($ClassLibraryRegistration)) {
      $Nodes = AblePolecat_Dom::getElementsByTagName($coreFile, 'class');
      self::insertList($Database, $ClassLibraryRegistration, $Nodes);
    }
    
    //
    // Load master project configuration file.
    //
    $masterProjectConfFile = AblePolecat_Mode_Config::getMasterProjectConfFile();
    
    //
    // Get package (class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'package');
    $applicationNode = $Nodes->item(0);
    if (!isset($applicationNode)) {
      $message = 'project.xml must contain an package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    //
    // Get list of package classes.
    //
    $applicationClassLibraryId = $applicationNode->getAttribute('id');
    $ClassLibraryRegistration = $ClassLibraryRegistry->getRegistrationById($applicationClassLibraryId);
    if (isset($ClassLibraryRegistration)) {
      $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'class');
      self::insertList($Database, $ClassLibraryRegistration, $Nodes);
    }
  }
  
  /**
   * Update current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if update fails.
   */
  public static function update(AblePolecat_DatabaseInterface $Database) {
    //
    // Load class library registry.
    //
    $ClassLibraryRegistry = AblePolecat_Registry_ClassLibrary::wakeup();
    
    //
    // Get current registrations.
    //
    $Registry = AblePolecat_Registry_Class::wakeup();
    $CurrentRegistrations = $Registry->getRegistrations(self::KEY_ARTICLE_ID);
    
    //
    // Make a list of potential delete candidates.
    //
    $CurrentRegistrationIds = array_flip(array_keys($CurrentRegistrations));
        
    //
    // Core class library conf file.
    //
    $coreFile = AblePolecat_Mode_Config::getCoreClassLibraryConfFile();
    
    //
    // Get package (core class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($coreFile, 'package');
    $corePackageNode = $Nodes->item(0);
    if (!isset($corePackageNode)) {
      $message = 'core class library configuration file must contain a package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    //
    // Create DML statements for classes.
    //
    $coreClassLibraryId = $corePackageNode->getAttribute('id');
    $ClassLibraryRegistration = $ClassLibraryRegistry->getRegistrationById($coreClassLibraryId);
    $Nodes = AblePolecat_Dom::getElementsByTagName($coreFile, 'class');
    foreach($Nodes as $key => $Node) {
      self::insertNode($Database, ClassLibraryRegistration, $Node);
      
      //
      // Since entry is in master project conf file, remove it from delete list.
      //
      $id = $Node->getAttribute('id');
      if (isset($CurrentRegistrationIds[$id])) {
        unset($CurrentRegistrationIds[$id]);
      }
    }
    
    //
    // Load master project configuration file.
    //
    $masterProjectConfFile = AblePolecat_Mode_Config::getMasterProjectConfFile();
    
    //
    // Get package (class library) id.
    //
    $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'package');
    $applicationNode = $Nodes->item(0);
    if (!isset($applicationNode)) {
      $message = 'project.xml must contain an package node.';
      AblePolecat_Command_Chain::triggerError($message);
    }
    
    //
    // Create DML statements for classes.
    //
    $applicationClassLibraryId = $applicationNode->getAttribute('id');
    $ClassLibraryRegistration = $ClassLibraryRegistry->getRegistrationById($applicationClassLibraryId);
    $Nodes = AblePolecat_Dom::getElementsByTagName($masterProjectConfFile, 'class');
    foreach($Nodes as $key => $Node) {
      self::insertNode($Database, ClassLibraryRegistration, $Node);
      
      //
      // Since entry is in master project conf file, remove it from delete list.
      //
      $id = $Node->getAttribute('id');
      if (isset($CurrentRegistrationIds[$id])) {
        unset($CurrentRegistrationIds[$id]);
      }
    }
    
    //
    // Remove any registered classes not in master project conf file.
    //
    if (count($CurrentRegistrationIds)) {
      $sql = __SQL()->
        delete()->
        from('class')->
        where(sprintf("`id` IN ('%s')", implode("','", array_flip($CurrentRegistrationIds))));
      $Database->execute($sql);
    }
    
    //
    // @todo: Refresh.
    //
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_RegistryInterface.
   ********************************************************************************/
  
  /**
   * Add a registry entry.
   *
   * @param AblePolecat_Registry_EntryInterface $RegistryEntry
   *
   * @throw AblePolecat_Registry_Exception If entry is incompatible.
   */
  public function addRegistration(AblePolecat_Registry_EntryInterface $RegistryEntry) {
    
    if (is_a($RegistryEntry, 'AblePolecat_Registry_Entry_ClassInterface')) {
      //
      // Include file if not already.
      //
      $include_path = $RegistryEntry->getClassFullPath();
      $include_path = AblePolecat_Server_Paths::includeFile($include_path);
      if (!$include_path) {
        throw new AblePolecat_Registry_Exception(sprintf("Cannot add registration to %s. Invalid path given for %s (%s).",
          __CLASS__,
          $RegistryEntry->getName(),
          $RegistryEntry->getClassFullPath()
        ));
      }
      
      //
      // Add to base registry class.
      //
      parent::addRegistration($RegistryEntry);
      
      //
      // Register by interface name(s).
      //
      $interfaces = class_implements($RegistryEntry->name, FALSE);
      foreach($interfaces as $interfaceName) {
        $id = $RegistryEntry->id;
        $name = $RegistryEntry->name;
        if (!isset($this->InterfaceImplementations[$interfaceName])) {
          $this->InterfaceImplementations[$interfaceName] = array(
            self::KEY_ARTICLE_ID => array(),
            self::KEY_CLASS_NAME => array(),
          );
        }
        if (!isset($this->InterfaceImplementations[$interfaceName][self::KEY_ARTICLE_ID][$id])) {
          $this->InterfaceImplementations[$interfaceName][self::KEY_ARTICLE_ID][$id] = $RegistryEntry;
        }
        if (!isset($this->InterfaceImplementations[$interfaceName][self::KEY_CLASS_NAME][$name])) {
          $this->InterfaceImplementations[$interfaceName][self::KEY_CLASS_NAME][$name] = $RegistryEntry;
        }
      }
    }
    else {
      throw new AblePolecat_Registry_Exception(sprintf("Cannot add registration to %s. %s does not implement %s.",
        __CLASS__,
        AblePolecat_Data::getDataTypeName($RegistryEntry),
        'AblePolecat_Registry_Entry_ClassInterface'
      ));
    }
  }
  
  /**
   * Retrieve a list of registered objects corresponding to the given key name/value.
   *
   * @param string $keyName The name of a registry key.
   * @param string $keyValue Optional value of registry key.
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public function getRegistrations($key, $value = NULL) {
    
    $Registrations = array();
    
    if ($key === self::KEY_INTERFACE) {
      $Registrations = $this->getInterfaceImplementations($value);
    }
    else {
      $Registrations = parent::getRegistrations($key, $value);
    }
    return $Registrations;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_ClassInterface.
   ********************************************************************************/
  
  /**
   * Return a list of all classes implementing given interface.
   *
   * @param string $interfaceName
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public function getInterfaceImplementations($interfaceName = NULL) {
    
    $Registrations = array();
    
    if (isset($interfaceName)) {
      if (isset($this->InterfaceImplementations[self::KEY_INTERFACE][$interfaceName])) {
        $Registrations = $this->InterfaceImplementations[self::KEY_INTERFACE][$interfaceName];
      }
    }
    else {
      $Registrations = $this->InterfaceImplementations;
    }
    return $Registrations;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Check if class can be loaded in current environment.
   * 
   * @param string $className The name of class to check for.
   *
   * @return AblePolecat_Registry_Entry_ClassInterface Class registration entry OR NULL.
   */
  public function isLoadable($className) {
    
    if (!is_scalar($className)) {
      $message = sprintf("%s requires first parameter to be scalar type. %s passed.", __METHOD__, AblePolecat_Data::getDataTypeName($className));
      throw new AblePolecat_Registry_Exception($message);
    }
    
    //
    // Try registration by name.
    //
    $RegistryEntry = $this->getRegistrationByName($className);
    if (!isset($RegistryEntry)) {
      //
      // By id?
      //
      $RegistryEntry = $this->getRegistrationById($className);
    }
    if (isset($RegistryEntry)) {
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, "$className is registered.");
    }
    else {
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, "$className is not registered. Attempt to load as core class.");
      $RegistryEntry = $this->getRegistrationByName($className);
    }
    return $RegistryEntry;
  }
  
  /**
   * Get instance of given class.
   *
   * @param mixed $classInfo AblePolecat_Registry_Entry_ClassInterface or string.
   * @param mixed $param Zero or more optional parameters to be passed to creational method.
   * 
   * @return object Instance of given class or NULL.
   */
  public function loadClass($classInfo, $param = NULL) {
    
    $Instance = NULL;
    $parameters = array();
    $RegistryEntry = NULL;
    
    if (is_object($classInfo) && is_a($classInfo, 'AblePolecat_Registry_Entry_ClassInterface')) {
      $RegistryEntry = $classInfo;
    }
    else {
      if (is_string('$classInfo')) {
        $RegistryEntry = $this->isLoadable($classInfo);
      }
    }
    
    //
    // Boot log is used for troubleshooting faulty extension code.
    //
    // $message = sprintf("%s registration is %s.", $className, AblePolecat_Data::getDataTypeName($RegistryEntry));
    // AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
    
    if ($RegistryEntry) {
      //
      // Get any parameters passed.
      //
      if (isset($param)) {
        $args = func_get_args();
        array_shift($args);
        $parameters = $args;
      }
      switch ($RegistryEntry->getClassFactoryMethod()) {
        default:
          $Instance = call_user_func_array(
            array(
              $RegistryEntry->getName(), 
              $RegistryEntry->getClassFactoryMethod()
            ), 
            $parameters
          );
          break;
        case '__construct':
          $Instance = new $RegistryEntry->getName();
          break;
      }
    }
    
    if (!isset($Instance)) {
      $message = sprintf("Could not load class %s with parameter set %s.", $RegistryEntry->getName(), serialize($parameters));
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
    }
    return $Instance;
  }
  
  /**
   * Register core (built-in) classes in case of no database connection.
   */
  private function registerCoreClasses() {
    //
    // There are a few classes required to install database and registries.
    //
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Resource_Core_Ack::UUID;
    $RegistryEntry->name = 'AblePolecat_Resource_Core_Ack';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Core', 'Ack.php'));
    $RegistryEntry->classFactoryMethod = 'wakeup';
    self::$Registry->addRegistration($RegistryEntry);
    
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Resource_Restricted_Install::UUID;
    $RegistryEntry->name = 'AblePolecat_Resource_Restricted_Install';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Restricted', 'Install.php'));
    $RegistryEntry->classFactoryMethod = 'wakeup';
    self::$Registry->addRegistration($RegistryEntry);
    
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Transaction_Restricted_Install::UUID;
    $RegistryEntry->name = 'AblePolecat_Transaction_Restricted_Install';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Install.php'));
    $RegistryEntry->classFactoryMethod = 'wakeup';
    self::$Registry->addRegistration($RegistryEntry);
    
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Resource_Restricted_Update::UUID;
    $RegistryEntry->name = 'AblePolecat_Resource_Restricted_Update';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Restricted', 'Update.php'));
    $RegistryEntry->classFactoryMethod = 'wakeup';
    self::$Registry->addRegistration($RegistryEntry);
    
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Transaction_Restricted_Update::UUID;
    $RegistryEntry->name = 'AblePolecat_Transaction_Restricted_Update';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted', 'Update.php'));
    $RegistryEntry->classFactoryMethod = 'wakeup';
    self::$Registry->addRegistration($RegistryEntry);
    
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Message_Response_Xml::UUID;
    $RegistryEntry->name = 'AblePolecat_Message_Response_Xml';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xml.php'));
    $RegistryEntry->classFactoryMethod = 'create';
    self::$Registry->addRegistration($RegistryEntry);
    
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Message_Response_Xhtml::UUID;
    $RegistryEntry->name = 'AblePolecat_Message_Response_Xhtml';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xhtml.php'));
    $RegistryEntry->classFactoryMethod = 'create';
    self::$Registry->addRegistration($RegistryEntry);
    
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Message_Response_Xhtml_Tpl::UUID;
    $RegistryEntry->name = 'AblePolecat_Message_Response_Xhtml_Tpl';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xhtml', 'Tpl.php'));
    $RegistryEntry->classFactoryMethod = 'create';
    self::$Registry->addRegistration($RegistryEntry);
    
    //
    // @todo: not really needed, core anyway.
    //
    $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
    $RegistryEntry->id = AblePolecat_Message_Response_Cached::UUID;
    $RegistryEntry->name = 'AblePolecat_Message_Response_Cached';
    $RegistryEntry->classFullPath = implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Cached.php'));
    $RegistryEntry->classFactoryMethod = 'create';
    self::$Registry->addRegistration($RegistryEntry);
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
   * DEPRECATED
   *
   * @param $className Name of class to get include file path for.
   * @param $extension File extension.
   *
   * @return AblePolecat_Registry_Entry_ClassInterface Class registration entry OR NULL.
   */
  public function registerByConvention($className, $extension = 'php') {
    
    $RegistryEntry = NULL;
    
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
          $interfaces = class_implements($className);
          if ($interfaces) {
            $interfaces = array_flip($interfaces);
            if (isset($interfaces['AblePolecat_Registry_Entry_ClassInterface'])) {
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
              $RegistryEntry = AblePolecat_Registry_Entry_Class::create();
              $RegistryEntry->id = $className::getId();
              $RegistryEntry->name = $className;
              // $RegistryEntry->classLibraryId;
              $RegistryEntry->classFullPath = $include_path;
              $RegistryEntry->classFactoryMethod = $method;
              $this->addRegistration($RegistryEntry);
            }
          }
        }
      }
    return $RegistryEntry;
  }
  
  /**
   * Insert DOMNodeList into registry.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   * @param AblePolecat_Registry_Entry_ClassLibrary $ClassLibraryRegistration 
   * @param DOMNodeList $Nodes List of DOMNodes containing registry entries.
   *
   */
  protected static function insertList(
    AblePolecat_DatabaseInterface $Database, 
    AblePolecat_Registry_Entry_ClassLibrary $ClassLibraryRegistration,
    DOMNodeList $Nodes) {
    foreach($Nodes as $key => $Node) {
      self::insertNode($Database, $ClassLibraryRegistration, $Node);
    }
  }
  
  /**
   * Insert DOMNode into registry.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   * @param AblePolecat_Registry_Entry_ClassLibrary $ClassLibraryRegistration 
   * @param DOMNode $Node DOMNode containing registry entry.
   *
   */
  protected static function insertNode(
    AblePolecat_DatabaseInterface $Database, 
    AblePolecat_Registry_Entry_ClassLibrary $ClassLibraryRegistration,
    DOMNode $Node) {

    if (!isset(self::$Registry)) {
      $message = __METHOD__ . ' Cannot call method before registry class is initialized.';
      AblePolecat_Command_Chain::triggerError($message);
    }

    $registerFlag = $Node->getAttribute('register');
    if ($registerFlag != '0') {
      $RegistryEntry = AblePolecat_Registry_Entry_Class::import($Node);
      $RegistryEntry->classLibraryId = $ClassLibraryRegistration->id;
      if (!isset($RegistryEntry->classFullPath)) {
        foreach($Node->childNodes as $key => $childNode) {
          if ($childNode->nodeName == 'polecat:path') {
            $conventionalPath = $ClassLibraryRegistration->libFullPath . DIRECTORY_SEPARATOR . $childNode->nodeValue;
            $sanitizePath = AblePolecat_Server_Paths::sanitizePath($conventionalPath);
            if (AblePolecat_Server_Paths::verifyFile($sanitizePath)) {
              $RegistryEntry->classFullPath = $sanitizePath;
            }
            break;
          }
        }
      }
      $RegistryEntry->save($Database);
      self::$Registry->addRegistration($RegistryEntry);
    }
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    parent::initialize();
    $this->InterfaceImplementations = array();
  }
}
