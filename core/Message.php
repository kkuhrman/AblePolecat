<?php
/**
 * @file
 * Interface for all Able Polecat messages passed to service bus.
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'DynamicObject.php');

interface AblePolecat_MessageInterface extends AblePolecat_DynamicObjectInterface {
}

abstract class AblePolecat_MessageAbstract extends AblePolecat_DynamicObjectAbstract implements AblePolecat_MessageInterface {
  
  /**
   * Helper funciton for outputting message as text.
   */
  protected function CRLF() {
    return sprintf("%c%c", 13, 10);
  }
}

/**
  * Exceptions thrown by Able Polecat message sub-classes.
  */
class AblePolecat_Message_Exception extends AblePolecat_Exception {
}
