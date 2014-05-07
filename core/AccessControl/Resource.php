<?php
/**
 * @file      polecat/core/AccessControl/Resource.php
 * @brief     Encapsulates an access control 'object'.
 * 
 * An access control object is a resource secured by constraints which agents may 
 * seek to gain access to; e.g. a file, a device, a database connection, etc.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article.php')));

interface AblePolecat_AccessControl_ResourceInterface extends AblePolecat_AccessControl_ArticleInterface {
}