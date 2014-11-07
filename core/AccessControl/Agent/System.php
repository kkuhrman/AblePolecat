<?php
/**
 * @file      polecat/core/AccessControl/Agent/System.php
 * @brief     Built-in system agent.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));

class AblePolecat_AccessControl_Agent_System extends AblePolecat_AccessControl_AgentAbstract {
  
  /**
   * @var AblePolecat_AccessControl_Agent_System Instance of singleton.
   */
  private static $System;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * System unique ID.
   *
   * @return scalar Subject unique identifier.
   */
  public function getId() {
    return 1;
  }
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public function getName() {
    return 'system';
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
   * @return AblePolecat_AccessControl_Agent_System Initialized access control service or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$System)) {
      //
      // Intentionally do not pass AblePolecat_Host to constructor as this would save
      // it as default command invoker. By default, commands will be dispatched to top
      // of system CoR hierarchy.
      //
      self::$System = new AblePolecat_AccessControl_Agent_System();
      AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'System agent initialized.');
    }
    return self::$System;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
  }
}