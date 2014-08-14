<?php
/**
 * @file      polecat/core/Resource/Util.php
 * @brief     Default base resource class for application utilities.
 *
 * Responds to request for 'built-in' utilities (delivered with core), such as 
 * installation and core updates.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource', 'Restricted.php')));

class AblePolecat_Resource_Util extends AblePolecat_Resource_RestrictedAbstract {
  
  /**
   * Constants.
   */
  const UUID = '50919dc0-1e43-11e4-8c21-0800200c9a66';
  const NAME = 'util';
  
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
   * @return Instance of AblePolecat_Resource_Util
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Util($Subject);
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
    
    //
    // Expected syntax: ./[util]/[name], where 'util' is directive for running application 
    // utilities (e.g. util) and 'name' is actual name of utility to run (e.g. www.example.com/util/update).
    //
    $request_path = AblePolecat_Server::getRequest()->getRequestPath(FALSE);
    if (!isset($request_path[0]) || ($request_path[0] == '') || (count($request_path) < 2)) {
      $request_path = AblePolecat_Server::getRequest()->getRequestPath();
      throw new AblePolecat_Resource_Exception($request_path . ' is not a valid request URI path for ' . __CLASS__ . '.');
    }
    $util_directive = array_shift($request_path);
    $this->Name = array_shift($request_path);
    if (count($request_path)) {
      $this->SubDir = $request_path;
    }
    $this->Args = AblePolecat_Server::getRequest()->getRequestQueryString(FALSE);
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}