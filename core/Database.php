<?php
/**
 * @file: Database.php
 * Base class for Able Polecat database clients.
 */

require_once(ABLE_POLECAT_PATH. DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Resource.php')));

interface AblePolecat_DatabaseInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
}

abstract class AblePolecat_DatabaseAbstract extends AblePolecat_AccessControl_ResourceAbstract implements AblePolecat_DatabaseInterface {
  
  /**
   * Opens an existing resource or makes an empty one accessible depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking access.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Existing or new resource.
   * @param string $name Optional common name for new resources.
   *
   * @return bool TRUE if access to resource is granted, otherwise FALSE.
   */
  abstract public function open(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_AccessControl_Resource_LocaterInterface $Url = NULL);
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}

class AblePolecat_Database_Exception extends AblePolecat_Exception {
}