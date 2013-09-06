<?php
/**
 * @file: Service.php
 * Base for web service clients, bus, resources, etc.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');

/**
 * Encapsulates a web service.
 */
interface AblePolecat_Service_Interface {
  /**
   * Serialize configuration and connection settings prior to going out of scope.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   */
  public function sleep(AblePolecat_AccessControl_AgentInterface $Agent = NULL);
  
  /**
   * Open a new connection or resume a prior connection.
   *
   * @param AblePolecat_AccessControl_AgentInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Service_Interface Initialized/connected instance of class ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_AgentInterface $Agent = NULL);
}

/**
 * Exceptions thrown by Able Polecat data sub-classes.
 */
class AblePolecat_Service_Exception extends AblePolecat_Exception {
}