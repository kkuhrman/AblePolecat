<?php
/**
 * @file      AblePolecat\usr\share\examples\basic\var\www\htdocs\index.php
 * @brief     All requests to Able Polecat are routed through index.php.
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 */
 
/**
 * Path settings.
 */
include_once('path.config');

/**
 * Route HTTP request.
 */
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Host.php')));
AblePolecat_Host::routeRequest();