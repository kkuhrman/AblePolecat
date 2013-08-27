<?php
/**
 * @file
 * Interface for all Able Polecat messages passed to service bus.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

interface AblePolecat_MessageInterface extends Serializable {
  
  /**
   * Create an 'empty' message (i.e. no trailing headers or body).
   *
   * HTTP HEAD request is an example of an 'empty' message.
   *
   * @param variable $tokens Minimal requirements for creating the message.
   *
   * @return AblePolecat_MessageAbstract New instance of message or NULL.
   */
  public static function createEmpty($tokens = NULL);
  
  /**
   * Create and format the entire message from the given tokens.
   *
   * @param variable $tokens All the data necessary to create entire message.
   *
   * @return AblePolecat_MessageAbstract New instance of message or NULL.
   */
  public static function create($tokens = NULL);
  
  /**
   * Output the entire message as text.
   */
  public function __toString();
}

/**
  * Exceptions thrown by Able Polecat message sub-classes.
  */
class AblePolecat_Message_Exception extends AblePolecat_Exception {
}
