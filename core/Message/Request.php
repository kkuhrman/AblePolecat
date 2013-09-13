<?php
/**
 * @file: Request.php
 * Base class for all request messages in Able Polecat.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Message.php');

abstract class AblePolecat_Message_RequestAbstract extends AblePolecat_MessageAbstract {
  
  /**
   * @var string Request resource (URI/URL).
   */
  private $m_resource;
  
  /**
   * @return string Request method.
   */
  abstract public function getMethod();
  
  /**
   * @return string Request resource (URI/URL).
   */
  public function getResource() {
    return $this->m_resource;
  }
  
  /**
   * Set request resource.
   * 
   * @param string $resource.
   */
  public function setResource($resource) {
    $this->m_resource = $resource;
  }
}