<?php
/**
 * @file: Request.php
 * Base class for all request messages in Able Polecat.
 *
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Message.php');
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Url.php');

interface AblePolecat_Message_RequestInterface extends AblePolecat_MessageInterface {
  
  /**
   * @return string Request method.
   */
  public function getMethod();
  
  /**
   * @return AblePolecat_Url Request resource (URI/URL).
   */
  public function getResource();
  
  /**
   * @todo: assign resource if building request to send to another server.
   */
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
    $this->m_resource = AblePolecat_Url::create();
  }
  
  /**
   * @return string Request resource (URI/URL).
   */
  public function getResource() {
    return $this->m_resource;
  }
}