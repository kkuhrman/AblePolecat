<?php
/**
 * @file: Registry.php
 * Object model of an RDBMS table/view for static (changes infrequently) data.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Registry.php')));

interface AblePolecat_RegistryInterface extends AblePolecat_CacheObjectInterface {
}

abstract class AblePolecat_RegistryAbstract extends AblePolecat_CacheObjectAbstract {
}