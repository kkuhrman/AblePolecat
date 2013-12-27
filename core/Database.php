<?php
/**
 * @file: Database.php
 * Base class for Able Polecat database clients.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource', 'Locater.php')));
require_once(ABLE_POLECAT_CORE. DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Database.php')));

interface AblePolecat_DatabaseInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
}

abstract class AblePolecat_DatabaseAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_DatabaseInterface {

  /**
   * @var AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource if any.
   */
  protected $Locater;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->Locater = NULL;
  }
  
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
   * Sets URL used to open resource.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Locater.
   */
  protected function setLocater(AblePolecat_AccessControl_Resource_LocaterInterface $Locater) {
    $this->Locater = $Locater;
  }
  
  /**
   * @return AblePolecat_AccessControl_Resource_LocaterInterface URL used to open resource or NULL.
   */
  public function getLocater() {
    return $this->Locater;
  }
}