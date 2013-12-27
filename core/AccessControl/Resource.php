<?php
/**
 * @file: Resource.php
 * The access control 'object', some resource secured by constraints which agents may 
 * seek to gain access to; e.g. a file, a device, a database connection, etc.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article.php')));

interface AblePolecat_AccessControl_ResourceInterface extends AblePolecat_AccessControl_ArticleInterface {
}