<?php
/**
 * @file      polecat/core/Environment/Application.php
 * @brief     Environment for Able Polecat Application Mode.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Component.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Response.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Template.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment.php')));

class AblePolecat_Environment_Application extends AblePolecat_EnvironmentAbstract {
  
  const UUID = 'df5e0c10-5f4d-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Application Environment';
  
  const VAR_REG_COMPONENT = 'AblePolecat_Registry_Component';
  const VAR_REG_CONNECTOR = 'AblePolecat_Registry_Connector';
  const VAR_REG_RESOURCE  = 'AblePolecat_Registry_Resource';
  const VAR_REG_RESPONSE  = 'AblePolecat_Registry_Response';
  const VAR_REG_TEMPLATE  = 'AblePolecat_Registry_Template';
    
  /**
   * @var AblePolecat_Environment_Server Singleton instance.
   */
  private static $Environment = NULL;
  
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
   * @return AblePolecat_Environment_Application or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Environment)) {
      //
      // Initialize singleton instance.
      //
      self::$Environment = new AblePolecat_Environment_Application($Subject);
      
      //
      // Component registry.
      //
      $ComponentRegistry = AblePolecat_Registry_Component::wakeup($Subject);
      self::$Environment->setVariable(
        $Subject,
        self::VAR_REG_COMPONENT,
        $ComponentRegistry
      );
      
      //
      // Connector registry.
      //
      $ConnectorRegistry = AblePolecat_Registry_Connector::wakeup($Subject);
      self::$Environment->setVariable(
        $Subject,
        self::VAR_REG_CONNECTOR,
        $ConnectorRegistry
      );
      
      //
      // Resource registry.
      //
      $ResourceRegistry = AblePolecat_Registry_Resource::wakeup($Subject);
      self::$Environment->setVariable(
        $Subject,
        self::VAR_REG_RESOURCE,
        $ResourceRegistry
      );
      
      //
      // Response registry.
      //
      $ResponseRegistry = AblePolecat_Registry_Response::wakeup($Subject);
      self::$Environment->setVariable(
        $Subject,
        self::VAR_REG_RESPONSE,
        $ResponseRegistry
      );
      
      //
      // Template registry.
      //
      $TemplateRegistry = AblePolecat_Registry_Template::wakeup($Subject);
      self::$Environment->setVariable(
        $Subject,
        self::VAR_REG_TEMPLATE,
        $TemplateRegistry
      );
      
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Application(s) environment initialized.');
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