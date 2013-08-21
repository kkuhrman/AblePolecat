<?php
/**
 * @file: boot.php
 * Execute bootstrap procedure for Able Polecat.
 */

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'pathdefs.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'bootmode.php');

$ABLE_POLECAT_ENVIRONMENT_INCL_PATH = ABLE_POLECAT_PATH  . DIRECTORY_SEPARATOR . 'Environment'   . DIRECTORY_SEPARATOR . 'Default.php';
if (!is_file($ABLE_POLECAT_ENVIRONMENT_INCL_PATH)) {
  die("Able Polecat failed to start. Invalid path for environment given: $ABLE_POLECAT_ENVIRONMENT_INCL_PATH");
}
else {
  require_once($ABLE_POLECAT_ENVIRONMENT_INCL_PATH);
  AblePolecat_Environment_Default::bootstrap();
}