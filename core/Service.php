<?php
/**
 * @file      polecat/core/Service.php
 * @brief     Interface for a service intermediary or end point.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
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
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}
