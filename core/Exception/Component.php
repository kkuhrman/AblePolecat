<?php
/**
 * @file      Exception/Component.php
 * @brief     Exceptions thrown by Able Polecat components.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

class AblePolecat_Component_Exception extends AblePolecat_Exception {
}