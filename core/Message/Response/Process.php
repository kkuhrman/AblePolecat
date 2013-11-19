<?php
/**
 * @file: Process.php
 * Default object for in-process response messages (as opposed to inter-process).
 */
class AblePolecat_Message_Response_Process extends AblePolecat_Message_ResponseAbstract {
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {
    $Response = new AblePolecat_Message_Response_Process();
    return $Response;
  }
}