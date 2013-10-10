<?php 
/**
 * @file: Module.php
 * Module configuration settings.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf.php');

class AblePolecat_Conf_Module extends AblePolecat_ConfAbstract {
  
  const UUID              = 'fff28c77-3e91-4f3b-98d1-1e3a5347f1df';
  const NAME              = 'module.xml';
  
  const ATTRIBUTE_NAME    = 'name';
  const ATTRIBUTE_DESC    = 'decsription';
  const ATTRIBUTE_REG     = 'register';
  const ATTRIBUTE_ID      = 'id';
  const ATTRIBUTE_PATH    = 'fullpath';
  
  const ELEMENT_ATTR      = 'attributes';
  const ELEMENT_CLASSES   = 'classes';
  const ELEMENT_CLASS     = 'class';
  const ELEMENT_LIBS      = 'libs';
  const ELEMENT_LIB       = 'lib';
  const ELEMENT_CLASSNAME = 'classname';
  const ELEMENT_INTERFACE = 'interface';
  const ELEMENT_FILENAME  = 'filename';
  const ELEMENT_FULLPATH  = 'fullpath';
  const ELEMENT_CLASSMETH = 'classFactoryMethod';
  
  /**
   * Extends __construct().
   */
  protected function initialize() {    
    parent::initialize();
  }
  
  /**
   * Return unique, system-wide identifier for security resource.
   *
   * @return string Resource identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security resource.
   *
   * @return string Resource name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /**
   * Creates empty resource.
   */
  public static function touch() {
    $Conf = new AblePolecat_Conf_Module();
    return $Conf;
  }
  
  /**
   * Helper function - retrieve module attributes element as an array.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking to read.
   *
   * @return Array Data read from conf file or NULL.
   */
  public function getModuleAttributes(AblePolecat_AccessControl_AgentInterface $Agent) {
    
    $moduleAttributes = FALSE;
    
    if (isset($this->m_SimpleXml) && $this->hasPermission($Agent, AblePolecat_AccessControl_Constraint_Read::getId())) {
      $moduleAttributes = array();
      $modAttrIter = $this->m_SimpleXml->{self::ELEMENT_ATTR}();
      isset($modAttrIter[self::ATTRIBUTE_NAME]) ? $moduleAttributes[self::ATTRIBUTE_NAME] = $modAttrIter[self::ATTRIBUTE_NAME]->__toString() : $moduleAttributes[self::ATTRIBUTE_NAME] = NULL;
      isset($modAttrIter[self::ATTRIBUTE_DESC]) ? $moduleAttributes[self::ATTRIBUTE_DESC] = $modAttrIter[self::ATTRIBUTE_DESC]->__toString() : $moduleAttributes[self::ATTRIBUTE_DESC] = NULL;
      isset($classAttributesIter[self::ATTRIBUTE_REG]) ? $classAttributes[self::ATTRIBUTE_REG] = intval($classAttributesIter[self::ATTRIBUTE_REG]) : $classAttributes[self::ATTRIBUTE_REG] = 0;
      isset($modAttrIter[self::ATTRIBUTE_ID]) ? $moduleAttributes[self::ATTRIBUTE_ID] = $modAttrIter[self::ATTRIBUTE_ID]->__toString() : $moduleAttributes[self::ATTRIBUTE_ID] = NULL;
      isset($modAttrIter[self::ATTRIBUTE_PATH]) ? $moduleAttributes[self::ATTRIBUTE_PATH] = $modAttrIter[self::ATTRIBUTE_PATH]->__toString() : $moduleAttributes[self::ATTRIBUTE_PATH] = NULL;
    }
    return $moduleAttributes;
  }
  
  /**
   * Helper function - retrieve module classes element as an array.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking to read.
   *
   * @return Array Data read from conf file or NULL.
   */
  public function getModuleClasses(AblePolecat_AccessControl_AgentInterface $Agent) {
    
    $moduleClasses = FALSE;
    
    if (isset($this->m_SimpleXml) && $this->hasPermission($Agent, AblePolecat_AccessControl_Constraint_Read::getId())) {
      
      //
      // Need module full path from attributes.
      //
      $moduleAttributes = array();
      $modAttrIter = $this->m_SimpleXml->{self::ELEMENT_ATTR}();
      isset($modAttrIter[self::ATTRIBUTE_PATH]) ? $moduleAttributes[self::ATTRIBUTE_PATH] = $modAttrIter[self::ATTRIBUTE_PATH]->__toString() : $moduleAttributes[self::ATTRIBUTE_PATH] = NULL;
      
      //
      // Module classes
      //
      if (isset($this->m_SimpleXml->classes)) {
        $moduleClasses = array();
        $modClassIter = $this->m_SimpleXml->{self::ELEMENT_CLASSES};
        foreach($modClassIter as $key => $class) {
          if(isset($class->{self::ELEMENT_CLASS})) {
            isset($class->{self::ELEMENT_CLASS}->{self::ELEMENT_CLASSNAME}) ? $class_name = $class->{self::ELEMENT_CLASS}->{self::ELEMENT_CLASSNAME}->__toString() : $class_name = FALSE;
            if ($class_name) {
              $moduleClasses[$class_name] = array();
              $moduleClasses[$class_name][self::ELEMENT_CLASSNAME] = $class_name;
              $classAttributes = array();
              $classAttributesIter = $class->{self::ELEMENT_CLASS}->attributes();
              isset($classAttributesIter[self::ATTRIBUTE_REG]) ? $classAttributes[self::ATTRIBUTE_REG] = intval($classAttributesIter[self::ATTRIBUTE_REG]->__toString()) : $classAttributes[self::ATTRIBUTE_REG] = 0;
              isset($classAttributesIter[self::ATTRIBUTE_ID]) ? $classAttributes[self::ATTRIBUTE_ID] = $classAttributesIter[self::ATTRIBUTE_ID]->__toString() : $classAttributes[self::ATTRIBUTE_ID] = NULL;
              $moduleClasses[$class_name][self::ELEMENT_ATTR] = $classAttributes;
              isset($class->{self::ELEMENT_CLASS}->{self::ELEMENT_INTERFACE}) ? $moduleClasses[$class_name][self::ELEMENT_INTERFACE] = $class->{self::ELEMENT_CLASS}->{self::ELEMENT_INTERFACE}->__toString() : $moduleClasses[$class_name][self::ELEMENT_INTERFACE] = NULL;
              isset($class->{self::ELEMENT_CLASS}->{self::ELEMENT_FILENAME}) ? $moduleClasses[$class_name][self::ELEMENT_FILENAME] = $class->{self::ELEMENT_CLASS}->{self::ELEMENT_FILENAME}->__toString() : $moduleClasses[$class_name][self::ELEMENT_FILENAME] = NULL;
              isset($class->{self::ELEMENT_CLASS}->{self::ELEMENT_CLASSMETH}) ? $moduleClasses[$class_name][self::ELEMENT_CLASSMETH] = $class->{self::ELEMENT_CLASS}->{self::ELEMENT_CLASSMETH}->__toString() : $moduleClasses[$class_name][self::ELEMENT_CLASSMETH] = NULL;
              $moduleClasses[$class_name][self::ELEMENT_FULLPATH] = $moduleAttributes[self::ATTRIBUTE_PATH] . DIRECTORY_SEPARATOR . $moduleClasses[$class_name][self::ELEMENT_FILENAME];
            }
          }
        }
      }
    }
    return $moduleClasses;
  }
  
  /**
   * Opens an existing resource or makes an empty one accessible depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking access.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Existing or new resource.
   * @param string $name Optional common name for new resources.
   *
   * @return bool TRUE if access to resource is granted, otherwise FALSE.
   */
  public function open(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_AccessControl_Resource_LocaterInterface $Url = NULL) {
    
    $open = FALSE;
    if ($this->hasPermission($Agent, AblePolecat_AccessControl_Constraint_Open::getId())) {
      //
      // Read configuration file.
      //
      $configFullPath = NULL;
      if (isset($Url) && is_file("$Url")) {
        $configFullPath = "$Url";
        $handle = fopen($configFullPath, "r");
        $xmldoc = fread($handle, filesize($configFullPath));
        $this->setLocater($Url);
        fclose($handle);
      }
      else {
        //
        // Default document.
        //
        $xmldoc = sprintf("<?xml version='1.0' standalone='yes'?><%s></%s>", 
          self::CONF_NAMESPACE . ':' . self::ELEMENT_MODULE,
          self::CONF_NAMESPACE . ':' . self::ELEMENT_MODULE);
      }
      try {
        @$this->m_SimpleXml = new SimpleXMLElement($xmldoc);
        $this->m_SimpleXml->addChild(self::CONF_NAMESPACE . ':' . self::ELEMENT_CLASS, get_class($this));
        $attributes = $this->m_SimpleXml->attributes();
        if (!isset($attributes['fullpath'])) {
          $pos = strripos($configFullPath, 'conf');
          $moduleFullpath = substr($configFullPath, 0, $pos - 1);
          $this->m_SimpleXml->addAttribute('fullpath',$moduleFullpath);
        }
        $open = TRUE;
      }
      catch(Exception $exception) {
        $this->logMessage("Failed to open module configuration file: " . $exception->getMessage());
        $this->m_SimpleXml = NULL;
      }
    }
    return $open;
  }
}