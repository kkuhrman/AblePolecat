<?php
/**
 * @file      polecat/core/Registry.php
 * @brief     Encapsulates a single core database table and provides system defaults.
 *
 * The Able Polecat core database comprises tables, which fall into one of two
 * main categories, registry and session. Session data includes HTTP requests, 
 * errors, logs, cached responses, access control settings and more. Registry 
 * data is a subset of environment configuration and includes PHP classes, 
 * components, connectors, resources, and responses. This data is initially saved
 * as XML in configuration files, which are used to populate the polecat database.
 * Classes implementing AblePolecat_RegistryInterface handle populating database
 * from configuration files and vice versa.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Schema.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Registry.php')));

interface AblePolecat_RegistryInterface 
  extends AblePolecat_CacheObjectInterface, AblePolecat_Database_InstallerInterface {
}

abstract class AblePolecat_RegistryAbstract extends AblePolecat_CacheObjectAbstract {
}