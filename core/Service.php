<?php
/**
 * @file: Service.php
 * Interface for a service intermediary or end point.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');

/**
 * Encapsulates a web service.
 */
interface AblePolecat_Service_Interface {
}

/**
 * Exceptions thrown by Able Polecat data sub-classes.
 */
class AblePolecat_Service_Exception extends AblePolecat_Exception {
}