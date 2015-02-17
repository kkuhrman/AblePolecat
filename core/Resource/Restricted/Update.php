<?php
/**
 * @file      polecat/core/Resource/Core/Update.php
 * @brief     Starting point for interactive update procedure.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Restricted.php')));

class AblePolecat_Resource_Restricted_Update extends AblePolecat_Resource_RestrictedAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  // private static $Resource;
  
  /**
   * Constants.
   */
  const UUID = 'e152865f-b6b3-11e4-a12d-0050569e00a2';
  const NAME = AblePolecat_Message_RequestInterface::RESOURCE_NAME_UPDATE;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_Resource_Restricted_Update
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Restricted_Update($Subject);
      self::$Resource->setWakeupAccessRequest(AblePolecat_AccessControl_Constraint_Execute::getId());
    }
    return parent::wakeup($Subject);
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
    // $request_path = AblePolecat_Host::getRequest()->getRequestPath(FALSE);
    // if (!isset($request_path[0]) || ($request_path[0] != 'update') || (count($request_path) > 1)) {
      // $request_path = AblePolecat_Host::getRequest()->getRequestPath();
      // throw new AblePolecat_Resource_Exception($request_path . ' is not a valid request URI path for ' . __CLASS__ . '.');
    // }
    return TRUE;
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