<?php 
/**
 * @file: Server.php
 * Encapsulates configuration file for server mode.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Server.php');
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf.php');

class AblePolecat_Conf_Server extends AblePolecat_ConfAbstract {
  
  const UUID              = 'fba4f890-60eb-11e2-bcfd-0800200c9a66';
  const NAME              = 'server.xml';
  
  /**
   * Extends __construct().
   */
  protected function initialize() {    
    parent::initialize();
  }
  
  /**
   * @return string Default name of directory where conf file should be located.
   */
  public static function getDefaultDir() {
    return ABLE_POLECAT_CONF_PATH;
  }
  
  /**
   * Returns location of the of conf file based on server mode.
   *
   * @return AblePolecat_AccessControl_Resource_LocaterInterface or FALSE.
   */
  public static function getResourceLocater() {
    
    $Locater = FALSE;
    
    //
    // Conf files are stored in one of two ways:
    // 1. One file for all modes ./root/conf/filename.xml
    // 2. Different files for specific modes ./root/conf/[mode]/filename.xml
    // If no mode specific file is found, #1 is used.
    //
    $path = self::getDefaultDir() . DIRECTORY_SEPARATOR . self::getName();
    if (!file_exists($path)) {
      $subdir = AblePolecat_Server::getBootMode();
      $path = self::getDefaultDir() . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR . self::getName();
      if (file_exists($path)) {
        $Locater = AblePolecat_AccessControl_Resource_Locater::create($subdir . DIRECTORY_SEPARATOR . self::getName(), 
          self::getDefaultDir());
      }
    }
    else {
      $Locater = AblePolecat_AccessControl_Resource_Locater::create(self::getName(), self::getDefaultDir());
    }
    
    return $Locater;
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
    $Conf = new AblePolecat_Conf_Server();
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
      if (isset($Url) && is_file("$Url")) {
        $filename = "$Url";
        $handle = fopen($filename, "r");
        $xmldoc = fread($handle, filesize($filename));
        $this->setLocater($Url);
        fclose($handle);
      }
      else {
        //
        // Default document.
        //
        $xmldoc = sprintf("<?xml version='1.0' standalone='yes'?><%s></%s>", 
          self::CONF_NAMESPACE . ':' . self::ELEMENT_ROOT,
          self::CONF_NAMESPACE . ':' . self::ELEMENT_ROOT);
      }
      try {
        @$this->m_SimpleXml = new SimpleXMLElement($xmldoc);
        $this->m_SimpleXml->addChild(self::CONF_NAMESPACE . ':' . self::ELEMENT_CLASS, get_class($this));
        $open = TRUE;
      }
      catch(Exception $exception) {
        AblePolecat_Server::handleCriticalError($exception->getCode(), 
          "Failed to open application configuration file: " . $exception->getMessage());
        $this->m_SimpleXml = NULL;
      }
    }
    return $open;
  }
}