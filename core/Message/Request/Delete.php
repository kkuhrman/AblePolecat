<?php
/**
 * @file: Delete.php
 * Encapsulates an Able Poelcat DELETE request.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request.php')));

class AblePolecat_Message_Request_Delete extends AblePolecat_Message_RequestAbstract {
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @param Array $head Optional message header fields (NVP).
   * @param mixed $body Optional message body.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create($head = NULL, $body = NULL) {
    
    $Request = new AblePolecat_Message_Request_Delete();
    $Request->setHead($head);
    $Request->setBody($body);
    return $Request;
  }
  
  /**
   * @return string Request method.
   */
  public function getMethod() {
    return 'DELETE';
  }
}