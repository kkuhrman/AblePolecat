<?php
/**
 * @file      polecat/core/Environment/Application.php
 * @brief     Environment for Able Polecat Application Mode.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment.php')));

class AblePolecat_Environment_Application extends AblePolecat_EnvironmentAbstract {
  
  const UUID = 'df5e0c10-5f4d-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Application Environment';
    
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
      try {
        //
        // Initialize singleton instance.
        //
        self::$Environment = new AblePolecat_Environment_Application($Subject);
        
        AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, 'Application(s) environment initialized.');
      }
      catch (Exception $Exception) {
        throw new AblePolecat_Environment_Exception("Failure to initialize application(s) environment. " . $Exception->getMessage(), 
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