<?php
/**
 * @file      polecat/core/AccessControl/Constraint.php
 * @brief     The opposite (denial) of a permission; e.g. deny_write = TRUE.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article.php')));

interface AblePolecat_AccessControl_ConstraintInterface extends AblePolecat_AccessControl_ArticleInterface {
}