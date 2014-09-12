<?php
/**
 * @file      polecat/core/Resource/Core.php
 * @brief     A helper class for loading one of the core (built-in) resources.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Ack.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Error.php')));

class AblePolecat_Resource_Core extends AblePolecat_ResourceAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource;
  
  /**
   * Constants.
   */
  const UUID = '80d53f20-3a93-11e4-916c-0800200c9a66';
  const NAME = 'Able Polecat Core Resource';
  
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
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_ResourceInterface
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      $args = func_get_args();
      isset($args[1]) ? $className = $args[1] : $className = 'null';      
      switch ($className) {
        default:
          self::$Resource = AblePolecat_Resource_Error::wakeup();
          isset($args[2]) ? self::$Resource->Reason = $args[2] : self::$Resource->Reason = 'Unknown';
          isset($args[3]) ? self::$Resource->Message = $args[3] : self::$Resource->Message = 'Unknown error in Able Polecat.';
          break;
        case 'AblePolecat_Resource_Ack':
          self::$Resource = AblePolecat_Resource_Ack::wakeup();
          break;
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
}