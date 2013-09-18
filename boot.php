<?php
/**
 * @file: boot.php
 * Execute bootstrap procedure for Able Polecat.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'core_require.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'core', 'Server.php')));

//
// Additional user-defined constants etc. prior to bootstrap
// (e.g. one common usage would be to define data directories).
//
$data_path = implode(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), 'Private', 'AblePolecat', 'data'));
AblePolecat_Server_Paths::setFullPath('data', $data_path);
AblePolecat_Server_Paths::setFullPath('session', $data_path . DIRECTORY_SEPARATOR . 'session');

//
// Bootstrap Able Polecat server.
//
AblePolecat_Server::bootstrap();