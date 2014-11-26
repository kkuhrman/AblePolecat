<?php
/**
 * @file      polecat/core/Resource/Core/Ack.php
 * @brief     The default resource for normal operation if none other is defined.
 *
 * A throwback to the good, old days of programming... sometimes all you want to hear from
 * a computer is ACK.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Core.php')));

class AblePolecat_Resource_Core_Ack extends AblePolecat_Resource_CoreAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource;
  
  /**
   * Constants.
   */
  const UUID = '8de22e10-1e43-11e4-8c21-0800200c9a66';
  const NAME = AblePolecat_Message_RequestInterface::RESOURCE_NAME_ACK;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_Resource_Core_Ack
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Core_Ack();
      $version = AblePolecat_Version::getVersion(FALSE);
      foreach($version as $propertyName => $propertyValue) {
        self::$Resource->{$propertyName} = $propertyValue;
      }
    }
    return self::$Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Validates request URI path to ensure resource request can be fulfilled.
   *
   * @throw AblePolecat_Exception If request URI path is not validated.
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