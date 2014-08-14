<?php
/**
 * @file      polecat/core/Host.php
 * @brief     Base class for host acting as either client or server (request/response).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

if (!defined('URI_SLASH')) {
  $URI_SLASH = chr(0x2F);
  define('URI_SLASH', $URI_SLASH);
}
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Get.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Post.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Put.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Delete.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Host.php')));

interface AblePolecat_HostInterface extends AblePolecat_AccessControl_SubjectInterface {
  
  /**
   * @return AblePolecat_Message_RequestInterface
   */
  public static function getRequest();
    
  /**
   * Main point of entry for all Able Polecat page and service requests.
   *
   */
  public static function routeRequest();
}

abstract class AblePolecat_HostAbstract implements AblePolecat_HostInterface {
  
  /**
   * @var Instance of concrete singleton class.
   */
  protected static $Host = NULL;
  
  /**
   * @var Instance of AblePolecat_Message_RequestInterface.
   */
  protected $Request;
  
  /********************************************************************************
   * Implementation of AblePolecat_HostInterface
   ********************************************************************************/
  
  /**
   * @return AblePolecat_Message_RequestInterface
   */
  public static function getRequest() {
    
    if (!isset(self::$Host)) {
      throw new AblePolecat_Exception('Cannot call ' . __METHOD__ . ' prior to host being initialized.');
    }
    else if (!isset(self::$Host->Request)) {
      //
      // Build request.
      //
      isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
      switch ($method) {
        default:
          self::$Host->Request = AblePolecat_Message_Request_Unsupported::create();
          break;
        case 'GET':
          self::$Host->Request = AblePolecat_Message_Request_Get::create();
          break;
        case 'POST':
          self::$Host->Request = AblePolecat_Message_Request_Post::create();
          break;
        case 'PUT':
          self::$Host->Request = AblePolecat_Message_Request_Put::create();
          break;
        case 'DELETE':
          self::$Host->Request = AblePolecat_Message_Request_Delete::create();
          break;
      }
    }
    return self::$Host->Request;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
    
  /**
   * Extends __construct().
   */
  protected function initialize() {
    
    $this->Request = NULL;
  }
  
  final protected function __construct() {
    
    //
    // Turn on output buffering.
    //
    ob_start();
    
    //
    // Sub-classes can extend constructor via initialize().
    //
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    //
    // Flush output buffer.
    //
    ob_end_flush();
  }
}