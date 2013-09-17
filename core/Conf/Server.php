<?php 
/**
 * @file: Server.php
 * Encapsulates configuration file for server mode.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Server.php');
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Conf.php');

class AblePolecat_Conf_Server extends AblePolecat_ConfAbstract {
  
  const UUID              = 'fba4f890-60eb-11e2-bcfd-0800200c9a66';
  const NAME              = 'Server Able Polecat Configuration';
  
  /**
   * Extends __construct().
   */
  protected function initialize() {    
    parent::initialize();
  }
  
  /**
   * @return string Default name of sub-directory for conf file based on server mode.
   */
  public static function getDefaultSubDir() {
    return AblePolecat_Server::getBootMode();
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