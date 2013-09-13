<?php
/**
 * @file
 * Interface for all Able Polecat messages passed to service bus.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

interface AblePolecat_MessageInterface {
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @param Array $head Optional message header fields (NVP).
   * @param mixed $body Optional message body.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create($head = NULL, $body = NULL);
  
  /**
   * Return message BODY.
   *
   * @return mixed Message BODY or NULL.
   */
  public function getBody();
  
  /**
   * Return message HEAD or a specific header field.
   *
   * @param string $field_name Given if only part of HEAD is requested.
   *
   * @return mixed HEAD, value of header field given by $field_name or NULL.
   */
  public function getHead($field_name = NULL);
}

abstract class AblePolecat_MessageAbstract implements AblePolecat_MessageInterface {
  
  /**
   * @var BODY.
   */
  private $body;
  
  /**
   * @var HEAD.
   */
  private $head;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
  }
  
  /**
   * Set message BODY.
   *
   * @param mixed $body.
   */
  protected function setBody($body) {
    $this->body = $body;
  }
  
  /**
   * Set part or all of message HEAD.
   *
   * @param Array $head HEAD NVP(s).
   */
  protected function setHead($head) {
    if (isset($head) && is_array($head)) {
      foreach($head as $name => $value) {
        $this->head[$name] = $value;
      }
    }
  }
  
  /**
   * Helper funciton for outputting message as text.
   */
  protected function CRLF() {
    return sprintf("%c%c", 13, 10);
  }
  
  /**
   * Return message BODY.
   *
   * @return mixed Message BODY or NULL.
   */
  public function getBody() {
    return $this->body;
  }
  
  /**
   * Return message HEAD or a specific header field.
   *
   * @param string $field_name Given if only part of HEAD is requested.
   *
   * @return mixed HEAD, value of header field given by $field_name or NULL.
   */
  public function getHead($field_name = NULL) {
    $value = NULL;
    if (count($this->head)) {
      if (isset($this->head[$field_name])) {
        $value = $this->head[$field_name];
      }
      else {
        $value = $this->head;
      }
    }
    return $value;
  }
  
  final protected function __construct() {
    $this->body = NULL;
    $this->head = array();
    $this->initialize();
  }
}

/**
  * Exceptions thrown by Able Polecat message sub-classes.
  */
class AblePolecat_Message_Exception extends AblePolecat_Exception {
}
