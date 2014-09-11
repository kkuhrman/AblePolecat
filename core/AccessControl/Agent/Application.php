<?php
/**
 * @file      Application.php
 * @brief     Base class for access control for applications using Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));

class AblePolecat_AccessControl_Agent_Application extends AblePolecat_AccessControl_AgentAbstract {
  
  /**
   * Constants.
   */
  const UUID = '6c1c36d0-60bd-11e2-bcfd-0800200c9a66';
  const NAME = 'Application';
  
  /**
   * @var AblePolecat_AccessControl_Agent_Application Instance of singleton.
   */
  private static $Agent;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for agent.
   *
   * @return string Agent identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for agent.
   *
   * @return string Agent name.
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
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Agent)) {
      $Args = func_get_args();
      isset($Args[0]) ? $Subject = $Args[0] : $Subject = NULL;
      isset($Args[1]) ? $Mode = $Args[1] : $Mode = NULL;
      if (isset($Subject) && is_a($Subject, 'AblePolecat_AccessControl_Agent_Administrator')) {
        self::$Agent = new AblePolecat_AccessControl_Agent_Application($Mode);
      }
      else {
        $error_msg = sprintf("%s is not permitted to wakeup user access control agent.", AblePolecat_Data::getDataTypeName($Subject));
        throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
      }
    }
    return self::$Agent;
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
