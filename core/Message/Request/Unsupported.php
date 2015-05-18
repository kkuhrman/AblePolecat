<?php
/**
 * @file      polecat/core/Message/Request/Unsupported.php
 * @brief     Encapsulates an Able Polecat request for an unsupported method.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request.php')));

class AblePolecat_Message_Request_Unsupported extends AblePolecat_Message_RequestAbstract {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
   
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {
    $Request = new AblePolecat_Message_Request_Unsupported();
    return $Request;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Message_RequestInterface.
   ********************************************************************************/
  
  /**
   * @return string Request method.
   */
  public function getMethod() {
    isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = NULL;
    return $method;
  }
}