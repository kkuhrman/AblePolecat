<?php
/**
 * @file      polecat/core/Resource/Restricted.php
 * @brief     Default base class for restricted resources.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Resource.php');

abstract class AblePolecat_Resource_RestrictedAbstract extends AblePolecat_ResourceAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource = NULL;
  
  /**
   * Create class and return instance.
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return concrete instance of AblePolecat_Resource_RestrictedAbstract.
   */
  abstract protected static function getInstance(AblePolecat_AccessControl_SubjectInterface $Subject = NULL);
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_Resource_Util
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      $CommandResult = AblePolecat_Command_GetAccessToken::invoke($Subject, $Subject->getId(), self::getId());
      if ($CommandResult->success()) {
        //
        // @todo: check $CommandResult->value()
        //
        // self::$Resource = new AblePolecat_Resource_Util($Subject);
        self::$Resource = self::getInstance($Subject);
      }
      if (!isset(self::$Resource)) {
        AblePolecat_AccessControl::throwDenyAccessException($Subject, new AblePolecat_Resource_Util());
      }
      
    }
    return self::$Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    parent::initialize();
  }
}