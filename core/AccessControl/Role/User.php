<?php
/**
 * @file: User.php
 * Interface for all user access control roles.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));

interface AblePolecat_AccessControl_Role_UserInterface extends AblePolecat_AccessControl_RoleInterface {
}