<?php
/**
 * @file: ObjectReference.php
 * Interface for all Able Polecat messages passed to service bus.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Node.php')));

interface AblePolecat_Message_Node_ObjectReferenceInterface extends AblePolecat_Message_NodeInterface {
}

class AblePolecat_Message_Node_ObjectReference extends AblePolecat_Message_NodeAbstract implements AblePolecat_Message_Node_ObjectReferenceInterface {
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
  }
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @param Array $head Optional message header fields (NVP).
   * @param mixed $body Optional message body.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {
    
    $ObjRef = new AblePolecat_Message_Node_ObjectReference();
    return $ObjRef;
  }
}
