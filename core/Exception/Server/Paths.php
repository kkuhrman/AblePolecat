<?php
/**
 * @file      polecat/core/Exceptions/Server/Paths.php
 * @brief     Exceptions thrown by Able Polecat relating to system paths.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

class AblePolecat_Server_Paths_Exception extends AblePolecat_Exception {
}