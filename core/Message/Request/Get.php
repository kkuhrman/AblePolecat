<?php
/**
 * @file: Get.php
 * Encapsulates an Able Poelcat GET request.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request.php')));

class AblePolecat_Message_Request_Get extends AblePolecat_Message_RequestAbstract {
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {
    
    $Request = new AblePolecat_Message_Request_Get();
    return $Request;
  }
  
  /**
   * @return string Request method.
   */
  public function getMethod() {
    return 'GET';
  }
}