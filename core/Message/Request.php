<?php
/**
 * @file: Request.php
 * Base class for all request messages in Able Polecat.
 *
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Message.php');

interface AblePolecat_Message_RequestInterface extends AblePolecat_MessageInterface {
  
  /**
   * @return string Request method.
   */
  public function getMethod();
  
  /**
   * @return string Request resource (URI/URL).
   */
  public function getResource();
  
  /**
   * Set request resource.
   * 
   * @param string $resource.
   */
  public function setResource($resource);
}

abstract class AblePolecat_Message_RequestAbstract extends AblePolecat_MessageAbstract implements AblePolecat_Message_RequestInterface {
  
  /**
   * @var string Request resource (URI/URL).
   */
  private $m_resource;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->m_resource = NULL;
  }
  
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