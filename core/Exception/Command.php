<?php
/**
 * @file: Exception/Command.php
 * Exceptions thrown by Able Polecat commands, invokers and targets.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

class AblePolecat_Command_Exception extends AblePolecat_Exception {
}