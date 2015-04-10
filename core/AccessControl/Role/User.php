<?php
/**
 * @file      polecat/core/AccessControl/Role/User.php
 * @brief     Agent role for interactive user.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));

interface AblePolecat_AccessControl_Role_UserInterface extends AblePolecat_AccessControl_RoleInterface {
}