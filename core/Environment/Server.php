<?php
/**
 * @file      polecat/core/Environment/Server.php
 * @brief     Environment for Able Polecat Server Mode.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment.php')));

class AblePolecat_Environment_Server extends AblePolecat_EnvironmentAbstract {
  
  const UUID = '318df280-5def-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Server Environment';
  
  /**
   * Configuration file constants.
   */
  const CONF_FILENAME_HOST        = 'host.xml';
  
  /**
   * @var AblePolecat_Environment_Server Singleton instance.
   */
  private static $Environment = NULL;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
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
   * Return common name.
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
   * @return AblePolecat_Environment_Server or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Environment)) {
      try {
        //
        // Initialize singleton instance.
        //
        self::$Environment = new AblePolecat_Environment_Server($Subject);
        
        //
        // Get database settings from configuration file.
        //
        $confPath = implode(DIRECTORY_SEPARATOR, 
          array(
            AblePolecat_Server_Paths::getFullPath('conf'),
            self::CONF_FILENAME_HOST
          )
        );
        $Conf = new DOMDocument();
        $Conf->load($confPath);
        $DbNodes = AblePolecat_Dom::getElementsByTagName($Conf, 'database');
        $db_state = array();
        foreach($DbNodes as $key => $Node) {
          //
          // Only one instance of core (server mode) database can be active.
          // Otherwise, Able Polecat stops boot and throws exception.
          // @see ./polecat/etc/conf/host.xml
          // <database id="core" name="polecat" mode="server" use="1">
          //   <dsn>mysql://user:pass@localhost/polecat</dsn>
          // </database>
          //
          if (($Node->getAttribute('id') == 'core') &&
              ($Node->getAttribute('name') == 'polecat') && 
              ($Node->getAttribute('mode') == 'server') && 
              ($Node->getAttribute('use'))) 
          {
            $db_state['name'] = $Node->getAttribute('name');
            $db_state['mode'] = $Node->getAttribute('mode');
            $db_state['use'] = $Node->getAttribute('use');
            foreach($Node->childNodes as $key => $childNode) {
              if($childNode->nodeName == 'dsn') {
                $db_state['dsn'] = $childNode->nodeValue;
              }
            }
          }
        }
        
        //
        // Initialize system environment variables from conf file.
        //
        self::$Environment->setVariable(
          $Subject,
          AblePolecat_Server::SYSVAR_CORE_DATABASE,
          $db_state
        );
      }
      catch (Exception $Exception) {
        throw new AblePolecat_Environment_Exception("Failure to access/set application configuration. " . $Exception->getMessage(), 
          AblePolecat_Error::BOOTSTRAP_CONFIG);
      }
    }
    return self::$Environment;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    parent::initialize();
  }
}
