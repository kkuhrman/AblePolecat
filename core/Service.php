<?php
/**
 * @file: Service.php
 * Interface for a service intermediary or end point.
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Request.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Response.php')));

/**
 * Encapsulates a web service.
 */
interface AblePolecat_Service_Interface extends AblePolecat_CacheObjectInterface {
  
  /**
   * Initiate web service server and process request.
   *
   * handle() will respond to HTTP request or object passed as parameter, if applicable.
   * This permits service to be invoked by local host without having to send HTTP request.
   * 
   * @param AblePolecat_Message_RequestInterface $Request Optional.
   *
   * @return AblePolecat_Message_ResponseInterface or NULL.
   */
  public function handle(AblePolecat_Message_RequestInterface $Request = NULL);
}

/**
 * Exceptions thrown by Able Polecat data sub-classes.
 */
class AblePolecat_Service_Exception extends AblePolecat_Exception {
}