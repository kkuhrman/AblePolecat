<?php
/**
 * @file      Exception/Command.php
 * @brief     Exceptions thrown by Able Polecat commands, invokers and targets.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

class AblePolecat_Command_Exception extends AblePolecat_Exception {
}