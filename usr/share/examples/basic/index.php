<?php
/**
 * @file      polecat\usr\share\examples\basic\index.php
 * @brief     All requests to Able Polecat are routed through index.php.
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 */
 
/**
 * Root directory of the entire Able Polecat core project.
 */
if (!defined('ABLE_POLECAT_ROOT')) {
  $ABLE_POLECAT_ROOT = __DIR__;
  define('ABLE_POLECAT_ROOT', $ABLE_POLECAT_ROOT);
}

/**
 * Host-specific system-wide configuration files directory.
 * This constant IS used to locate the server configuration file and must define 
 * the full path of the parent of the conf directory if other than ABLE_POLECAT_ROOT/etc/conf.
 */
if (!defined('ABLE_POLECAT_ETC')) {
  $ABLE_POLECAT_ETC = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'etc';
  define('ABLE_POLECAT_ETC', $ABLE_POLECAT_ETC);
}

/**
 * Path settings.
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_ETC, 'polecat', 'conf', 'path.config')));

/**
 * Route HTTP request.
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Host.php')));
AblePolecat_Host::routeRequest();