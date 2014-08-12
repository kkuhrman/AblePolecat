<?php
/**
 * @file      polecat/core/AccessControl/Authority.php
 * @brief     Encapsulates an authentication service.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));

interface AblePolecat_AccessControl_AuthorityInterface extends AblePolecat_CacheObjectInterface {
  
  /**
   * Attempt to authenticate agent and assign corresponding role if successful.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent Agent requesting to be authenticated.
   *
   * @return Instance of AblePolecat_AccessControl_RoleInterface or NULL.
   */
  public function authenticate(AblePolecat_AccessControl_AgentInterface $Agent);
}

abstract class AblePolecat_AccessControl_AuthorityAbstract extends AblePolecat_CacheObjectAbstract {
  // AblePolecat_AccessControl_Authority_Database
}