<?php
/**
 * @file: Response.php
 * Base class for all response messages in Able Polecat.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Message.php');

interface AblePolecat_Message_ResponseInterface extends AblePolecat_MessageInterface {
}

abstract class AblePolecat_Message_ResponseAbstract extends AblePolecat_MessageAbstract implements AblePolecat_Message_ResponseInterface {
  
  /**
   * @var string The response status code (e.g. HTTP example would be 200).
   */
  private $m_status_code;
  
  /**
   * @var string The response reason phrase (e.g. HTTP example would be 'OK').
   */
  private $m_reason_phrase;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->m_status_code = 200;
    $this->m_reason_phrase = 'OK';
  }
  
  /**
   * Initialize the status code.
   *
   * @param string $status_code.
   */
  protected function setStatusCode($status_code) {
    $this->m_status_code = $status_code;
  }
  
  /**
   * Initialize the reason phrase.
   *
   * @param string $reason_phrase.
   */
  protected function setReasonPhrase($reason_phrase) {
    $this->m_reason_phrase = $reason_phrase;
  }
  
  /**
   * @return string The response status code.
   */
  public function getStatusCode() {
    return $this->m_status_code;
  }
  
  /**
   * @return string The response reason phrase.
   */
  public function getReasonPhrase() {
    return $this->m_reason_phrase;
  }
}

class AblePolecat_Message_Response extends AblePolecat_Message_ResponseAbstract {
    /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {
    $Response = new AblePolecat_Message_Response();
    return $Response;
  }
}