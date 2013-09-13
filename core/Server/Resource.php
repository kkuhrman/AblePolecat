<?php
/**
 * @file: Resource.php
 * Any object, which works on behalf of the server such as a web service client.
 */
 
include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'AccessControl', 'Agent.php')));
 
interface AblePolecat_Server_ResourceInterface {
  
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
   * @return AblePolecat_Server_ResourceInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_AgentInterface $Agent = NULL);
}
