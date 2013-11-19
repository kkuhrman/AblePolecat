<?php
/**
 * @file: Resource.php
 * Base class for file resources protected by access control.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Agent.php')));
include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Resource.php')));
include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Role.php')));

abstract class AblePolecat_AccessControl_Resource_FileAbstract extends AblePolecat_AccessControl_ResourceAbstract {
  
  /**
   * Creational function; similar to UNIX program, creates an empty resource.
   *
   * @return object Instance of class which implments AblePolecat_AccessControl_ResourceInterface.
   */
  abstract public static function touch();
  
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
   * Read from an existing resource or depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking to read.
   * @param string $start Optional offset to start reading from.
   * @param string $end Optional offset to end reading at.
   *
   * @return mixed Data read from resource or NULL.
   */
  abstract public function read(AblePolecat_AccessControl_AgentInterface $Agent, $start = NULL, $end = NULL);
  
  /**
   * Write to a new or existing resource or depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking to read.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Existing or new resource.
   * @param mixed $data The data to write to the resource.
   *
   * @return bool TRUE if write to resource is successful, otherwise FALSE.
   */
  abstract public function write(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_AccessControl_Resource_LocaterInterface $Url, $data);
}