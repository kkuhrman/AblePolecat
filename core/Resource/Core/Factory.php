<?php
/**
 * @file      polecat/core/Resource/Core/Factory.php
 * @brief     A helper class for loading one of the core (built-in) resources.
 *
 * A throwback to the good, old days of programming... sometimes all you want to hear from
 * a computer is ACK.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Core', 'Ack.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Core', 'Error.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Core', 'Form.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Core', 'Test.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Restricted', 'Install.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Restricted', 'Update.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource', 'Restricted', 'Util.php')));

class AblePolecat_Resource_Core_Factory extends AblePolecat_Resource_CoreAbstract {
    
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
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
          self::$Resource = AblePolecat_Resource_Core_Error::wakeup();
          self::$Resource->Reason = 'Core class does not exist.';
          self::$Resource->Message = sprintf("Able Polecat cannot load core class given by [%s].", $className);
          break;
        case 'AblePolecat_Resource_Core_Error':
          self::$Resource = AblePolecat_Resource_Core_Error::wakeup();
          isset($args[2]) ? self::$Resource->Reason = $args[2] : self::$Resource->Reason = 'Unknown Error.';
          isset($args[3]) ? self::$Resource->Message = $args[3] : self::$Resource->Message = 'Able Polecat directed to issue error response but no reason or message was given.';
          break;
        case 'AblePolecat_Resource_Core_Form':
          self::$Resource = AblePolecat_Resource_Core_Form::wakeup(AblePolecat_AccessControl_Agent_User::wakeup());
          break;
        case 'AblePolecat_Resource_Core_Ack':
          self::$Resource = AblePolecat_Resource_Core_Ack::wakeup();
          break;
        case 'AblePolecat_Resource_Restricted_Install':
          self::$Resource = AblePolecat_Resource_Restricted_Install::wakeup(AblePolecat_AccessControl_Agent_User::wakeup());
          break;
        case 'AblePolecat_Resource_Core_Test':
          self::$Resource = AblePolecat_Resource_Core_Test::wakeup(AblePolecat_AccessControl_Agent_User::wakeup());
          break;
        case 'AblePolecat_Resource_Restricted_Update':
          self::$Resource = AblePolecat_Resource_Restricted_Update::wakeup(AblePolecat_AccessControl_Agent_User::wakeup());
          break;
        case 'AblePolecat_Resource_Restricted_Util':
          //
          // @todo: authenticate
          //
          self::$Resource = AblePolecat_Resource_Restricted_Util::wakeup(AblePolecat_AccessControl_Agent_User::wakeup());
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