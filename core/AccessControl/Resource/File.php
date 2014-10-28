<?php
/**
 * @file      polecat/core/AccessControl/Resource/File.php
 * @brief     Base class for file resources protected by access control.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));

abstract class AblePolecat_AccessControl_Resource_FileAbstract extends AblePolecat_AccessControl_ResourceAbstract {
  
  /**
   * @var AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource if any.
   */
  protected $m_Locater;
  
  /**
   * Creational function; similar to UNIX program, creates an empty resource.
   *
   * @return object Instance of class which implments AblePolecat_AccessControl_ResourceInterface.
   */
  abstract public static function touch();
  
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
  
  /**
   * Extends __construct().
   *
   * Sub-classes should implement to initialize members in __construct().
   */
  protected function initialize() {
    $this->m_Locater = NULL;
  }
  
  /**
   * Sets URL used to open resource.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Locater.
   */
  protected function setLocater(AblePolecat_AccessControl_Resource_LocaterInterface $Locater) {
    $this->m_Locater = $Locater;
  }
  
  /**
   * @return AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource or NULL.
   */
  public function getLocater() {
    return $this->m_Locater;
  }
}