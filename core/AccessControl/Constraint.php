<?php
/**
 * @file:Constraint.php
 * The opposite, denial of a permission; e.g. deny_write = TRUE.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article.php')));

interface AblePolecat_AccessControl_ConstraintInterface extends AblePolecat_AccessControl_ArticleInterface {
}