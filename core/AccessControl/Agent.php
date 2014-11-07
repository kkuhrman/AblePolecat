<?php
/**
 * @file      polecat/core/AccessControl/Agent.php
 * @brief     The access control 'subject'; for example, a user.
 *
 * Intended to follow interface specified by W3C but does not provide public access to 
 * properties (get/set methods provided).
 *
 * @see http://www.w3.org/TR/url/#url
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');

interface AblePolecat_AccessControl_AgentInterface extends AblePolecat_AccessControl_SubjectInterface {
}

abstract class AblePolecat_AccessControl_AgentAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_AgentInterface {
  
  /**
   * @var Array[AblePolecat_AccessControl_RoleInterface].
   */
  private $ActiveRoles;
  
  /**
   * @var AblePolecat_Registry_Class Class Registry.
   */
  private $ClassRegistry;
     
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_AgentInterface.
   ********************************************************************************/
  
  /**
   * Return active role by id.
   *
   * @param string $roleId The ID of the active role, if assigned.
   *
   * @return mixed Instance of AblePolecat_AccessControl_RoleInterface, otherwise FALSE.
   */
  public function getActiveRole($roleId) {
    
    $Role = FALSE;
    if (isset($roleId) && is_scalar($roleId) && isset($this->ActiveRoles[$roleId])) {
      $Role = $this->ActiveRoles[$roleId];
    }
    return $Role;
  }
  
  /**
   * Returns a list of IDs of all assigned, active roles.
   *
   * @return Array.
   */
  public function getActiveRoleIds() {
    return array_keys($this->ActiveRoles);
  }
  
  /**
   * Load agent roles active for for current session.
   *
   * @return Array.
   */
  public function refreshActiveRoles() {
    
    $this->ActiveRoles = array();
    
    //
    // Get active roles from the database.
    //
    $sql = __SQL()->
      select('sessionNumber', 'roleId', 'userId', 'roleData')->
      from('role')->
      where(sprintf("sessionNumber = '%s'", AblePolecat_Host::getSessionNumber()));
    $CommandResult = AblePolecat_Command_DbQuery::invoke($this, $sql);
    if ($CommandResult->success()) {
      $results = $CommandResult->value();
      try {
        foreach($results as $key => $role) {
          //
          // assign roles to agent
          //
          $roleClassName = $role['roleId'];
          $Role = $this->getClassRegistry()->loadClass($roleClassName);
          if (isset($Role)) {
            $this->assignActiveRole($Role);
          }
          else {
            //
            // @todo: complain
            //
            AblePolecat_Command_Log::invoke($this, "Failed to load user role $roleClassName.", AblePolecat_LogInterface::WARNING);
          }
        }
      }
      catch (AblePolecat_Exception $Exception) {
        AblePolecat_Command_Log::invoke($this, $Exception->getMessage(), AblePolecat_LogInterface::WARNING);
      }
    }
    if (0 === count($this->ActiveRoles)) {
      //
      // No roles assigned, assume anonymous user.
      //
      $Role = $this->getClassRegistry()->loadClass('AblePolecat_AccessControl_Role_User_Anonymous');
      $this->assignActiveRole($Role);
    }
    
    return $this->ActiveRoles;
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Assign given role to agent on authority of subject.
   *
   * @param AblePolecat_AccessControl_RoleInterface $Role Assigned role.
   * @param $authorityId ID of authority granting role.
   */
  protected function assignActiveRole( 
    AblePolecat_AccessControl_RoleInterface $Role,
    $authorityId = NULL
  ) {
    
    if (!isset($this->ActiveRoles[$Role->getId()])) {
      $this->ActiveRoles[$Role->getId()] = $Role;
    }
  }
  
  /**
   * @return AblePolecat_Registry_Class.
   */
  protected function getClassRegistry() {
    
    if (!isset($this->ClassRegistry)) {
      $CommandResult = AblePolecat_Command_GetRegistry::invoke($this, 'AblePolecat_Registry_Class');
      if ($CommandResult->success()) {
        //
        // Save reference to class registry.
        //
        $this->ClassRegistry = $CommandResult->value();
      }
      else {
        throw new AblePolecat_AccessControl_Exception("Failed to retrieve class registry.");
      }
    }
    return $this->ClassRegistry;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->ActiveRoles = array();
    $this->ClassRegistry = NULL;
  }
}