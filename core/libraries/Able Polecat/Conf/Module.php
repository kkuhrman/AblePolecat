<?php 
/**
 * @file: Module.php
 * Module configuration settings.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf.php');

class AblePolecat_Conf_Module extends AblePolecat_ConfAbstract {
  
  const UUID              = 'fff28c77-3e91-4f3b-98d1-1e3a5347f1df';
  const NAME              = 'Able Polecat Module Configuration';
  
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