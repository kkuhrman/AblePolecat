<?php
/**
 * @file      polecat/core/Registry.php
 * @brief     Object model of an RDBMS table/view for static (changes infrequently) data.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Registry.php')));

interface AblePolecat_RegistryInterface extends AblePolecat_CacheObjectInterface {
}

abstract class AblePolecat_RegistryAbstract extends AblePolecat_CacheObjectAbstract {
}