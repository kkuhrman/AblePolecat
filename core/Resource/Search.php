<?php
/**
 * @file      polecat/core/Resource/Search.php
 * @brief     The default resource for 404 - not found.
 *
 * Intent is that sub-classes extend and redirect to search feature.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Resource.php');

class AblePolecat_Resource_Search extends AblePolecat_ResourceAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource;
  
  /**
   * Constants.
   */
  const UUID = 'a4933370-1e43-11e4-8c21-0800200c9a66';
  const NAME = 'search';
  
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
   * @return Instance of AblePolecat_Resource_Search
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Search();
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
    
    $request_path = AblePolecat_Server::getRequest()->getRequestPath(FALSE);
    if (!isset($request_path[0]) || ($request_path[0] === '')) {
      $request_path = AblePolecat_Server::getRequest()->getRequestPath();
      throw new AblePolecat_Resource_Exception($request_path . ' is not a valid request URI path for ' . __CLASS__ . '.');
    }
    $this->requestPath = $request_path;
  }
}