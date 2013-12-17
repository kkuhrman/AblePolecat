<?php
/**
 * @file: Service.php
 * Interface for a service intermediary or end point.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Initiator.php')));

/**
 * Encapsulates a web service.
 */
interface AblePolecat_Service_Interface extends AblePolecat_Service_InitiatorInterface {
}

/**
 * Base class for most services.
 */
abstract class AblePolecat_ServiceAbstract extends AblePolecat_Service_InitiatorAbstract implements AblePolecat_Service_Interface {
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}
