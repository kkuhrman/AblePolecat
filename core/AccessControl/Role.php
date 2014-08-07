<?php
/**
 * @file      polecat/core/AccessControl/Role.php
 * @brief     A job function within the system such as 'anonymous', 'authenticated', 'administrator' etc.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');

interface AblePolecat_AccessControl_RoleInterface extends AblePolecat_AccessControl_SubjectInterface, AblePolecat_CacheObjectInterface {
}

abstract class AblePolecat_AccessControl_RoleAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_RoleInterface {
}