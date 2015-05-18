<?php
/**
 * @file      polecat/core/AccessControl/Subject.php
 * @brief     'Subject' (agent or role) seeks access to 'Object' (resource).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Dynamic.php')));

interface AblePolecat_AccessControl_SubjectInterface extends AblePolecat_AccessControl_Article_DynamicInterface {
}