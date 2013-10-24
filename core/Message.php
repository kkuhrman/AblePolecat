<?php
/**
 * @file
 * Interface for all Able Polecat messages passed to service bus.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Node.php')));

interface AblePolecat_MessageInterface extends AblePolecat_Message_NodeInterface {
}

abstract class AblePolecat_MessageAbstract extends AblePolecat_Message_NodeAbstract implements AblePolecat_MessageInterface {
  
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
