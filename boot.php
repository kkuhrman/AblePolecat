<?php
/**
 * @file: boot.php
 * Execute bootstrap procedure for Able Polecat.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'core_require.php')));

//
// Additional user-defined constants etc. prior to bootstrap
// (e.g. one common usage would be to define data directories).
//
AblePolecat_Server_Paths::setFullPath('data', ABLE_POLECAT_DATA_PATH);
AblePolecat_Server_Paths::setFullPath('session', ABLE_POLECAT_DATA_PATH . DIRECTORY_SEPARATOR . 'session');

//
// Bootstrap Able Polecat server.
//
