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
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');

interface AblePolecat_AccessControl_AgentInterface extends AblePolecat_AccessControl_SubjectInterface {
}

abstract class AblePolecat_AccessControl_AgentAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_AgentInterface {
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    //
    // Agents invoke their own commands.
    //
    $this->setDefaultCommandInvoker($this);
  }
}