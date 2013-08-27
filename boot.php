<?php
/**
 * @file: boot.php
 * Execute bootstrap procedure for Able Polecat.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'core', 'Server.php')));

AblePolecat_Server::bootstrap();