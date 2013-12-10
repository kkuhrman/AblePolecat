<?php
/**
 * @file: Environment.php
 * Base class for Able Polecat Environment objects.
 *
 * Duties of the Environment object:
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

interface AblePolecat_EnvironmentInterface extends AblePolecat_AccessControl_SubjectInterface, AblePolecat_CacheObjectInterface {
}

/**
 * Exceptions thrown by environment objects.
 */
class AblePolecat_Environment_Exception extends AblePolecat_Exception {
}