<?php
/**
 * @file      polecat/core/Resource/Core/Error.php
 * @brief     The default resource in the event of application error.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Core.php')));

class AblePolecat_Resource_Core_Error extends AblePolecat_Resource_CoreAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource;
  
  /**
   * Constants.
   */
  const UUID = '9a5d85e0-1e43-11e4-8c21-0800200c9a66';
  const NAME = AblePolecat_Message_RequestInterface::RESOURCE_NAME_ERROR;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_Resource_Core_Error
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Core_Error();
    }
    return self::$Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Validates request URI path to ensure resource request can be fulfilled.
   *
   * @throw AblePolecat_Resource_Exception If request URI path is not validated.
   */
  protected function validateRequestPath() {
    //
    // Request path is irrelevant in this case.
    //
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->setId(self::UUID);
    $this->setName(self::NAME);
  }
}