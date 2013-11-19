<?php
/**
 * @file: Role.php
 * A job function within the system such as 'anonymous', 'authenticated', 'administrator' etc.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Agent.php')));
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'CacheObject.php');

interface AblePolecat_AccessControl_RoleInterface extends AblePolecat_AccessControl_SubjectInterface {
}

abstract class AblePolecat_AccessControl_RoleAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_SubjectInterface {
}