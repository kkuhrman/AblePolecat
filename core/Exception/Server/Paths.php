<?php
/**
 * @file: Exceptions/Server/Paths.php
 * Exceptions thrown by Able Polecat relating to system paths.
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Server.php')));

class AblePolecat_Server_Paths_Exception extends AblePolecat_Server_Exception {
}