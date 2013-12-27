<?php
/**
 * @file: Agent.php
 * The access control 'subject'; for example, a user.
 *
 * Intended to follow interface specified by W3C but does not provide public access to 
 * properties (get/set methods provided).
 *
 * @see http://www.w3.org/TR/url/#url
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');

interface AblePolecat_AccessControl_AgentInterface extends AblePolecat_AccessControl_SubjectInterface {
}

abstract class AblePolecat_AccessControl_AgentAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_AgentInterface {
}