<?php
/**
 * @file: Subject.php
 * 'Subject' (agent or role) seeks access to 'Object' (resource).
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article.php')));

interface AblePolecat_AccessControl_SubjectInterface extends AblePolecat_AccessControl_ArticleInterface {
}