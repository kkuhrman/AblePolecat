<?php
/**
 * @file      polecat/core/Registry.php
 * @brief     Encapsulates a single core database table and provides system defaults.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Registry.php')));

interface AblePolecat_RegistryInterface extends AblePolecat_CacheObjectInterface {
}

abstract class AblePolecat_RegistryAbstract extends AblePolecat_CacheObjectAbstract {
}