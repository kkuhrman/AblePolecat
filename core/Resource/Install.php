<?php
/**
 * @file      polecat/core/Resource/Install.php
 * @brief     Starting point for interactive install procedure.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Resource.php');

class AblePolecat_Resource_Install extends AblePolecat_ResourceAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource;
  
  /**
   * Constants.
   */
  const UUID = '3f3630b0-3a9d-11e4-916c-0800200c9a66';
  const NAME = AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL;
  
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
   * @return Instance of AblePolecat_Resource_Install
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Install();
      self::$Resource->activeCoreDatabase = AblePolecat_Host::getActiveCoreDatabaseName();
      
      $version = AblePolecat_Host::getVersion(TRUE, 'HTML');
      self::$Resource->Head = sprintf("<title>%s | Install</title>", $version);
      self::$Resource->Body = "<p>Detailed installation instructions are at <a href=\"https://github.com/kkuhrman/AblePolecat/wiki/Getting-Started\" target=\"new\">
        https://github.com/kkuhrman/AblePolecat/wiki/Getting-Started</a></p>";
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
    $request_path = AblePolecat_Host::getRequest()->getRequestPath(FALSE);
    if (!isset($request_path[0]) || ($request_path[0] != 'install') || (count($request_path) > 1)) {
      $request_path = AblePolecat_Host::getRequest()->getRequestPath();
      throw new AblePolecat_Resource_Exception($request_path . ' is not a valid request URI path for ' . __CLASS__ . '.');
    }
  }
}