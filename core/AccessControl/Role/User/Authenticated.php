<?php
/**
 * @file: Authenticated.php
 * Role reserved for anonymous agent (user).
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Role', 'User.php')));

interface AblePolecat_AccessControl_Role_User_AuthenticatedInterface extends AblePolecat_AccessControl_Role_UserInterface {
}
