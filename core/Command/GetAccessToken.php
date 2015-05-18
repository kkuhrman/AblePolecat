<?php
/**
 * @file      polecat/Command/GetAccessToken.php
 * @brief     Retrieve user access agent in scope for command invoker.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Reverse.php')));

class AblePolecat_Command_GetAccessToken extends AblePolecat_Command_ReverseAbstract {
  
  const UUID = 'bed41310-2174-11e4-8c21-0800200c9a66';
  const NAME = 'GetAccessToken';
  
  const ARG_AGENT_ID      = 'agentId';
  const ARG_CONSTRAINT_ID = 'constraintId';
  const ARG_RESOURCE_ID   = 'resourceId';
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
     
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CommandInterface.
   ********************************************************************************/
  
  /**
   * Invoke the command and return response from target.
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker Agent or role invoking command.
   *
   * @return AblePolecat_Command_Result.
   */
  public static function invoke(
    AblePolecat_AccessControl_SubjectInterface $Invoker, 
    $Arguments = NULL
  ) {
    //
    // Unmarshall and check command arguments
    //
    $VarArgs = func_get_args();
    $name = self::getName();
    $agentId = self::checkArgument(self::getName(), func_get_args(), 1, 'string');
    $resourceId = self::checkArgument(self::getName(), func_get_args(), 2, 'string');
    isset($VarArgs[3]) ? $constraintId = self::checkArgument($name, $VarArgs, 3, 'string') : $constraintId = AblePolecat_AccessControl_Constraint_Read::getId();
    $CommandArguments = array(
      self::ARG_AGENT_ID => $agentId,
      self::ARG_RESOURCE_ID  => $resourceId,
      self::ARG_CONSTRAINT_ID  => $constraintId,
    );
    
    //
    // Create and dispatch command
    //
    $Command = new AblePolecat_Command_GetAccessToken($Invoker, $CommandArguments);
    return $Command->dispatch();
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return string ID of agent requesting access.
   */
  public function getAgentId() {
    $args = $this->getArguments();
    isset($args[self::ARG_AGENT_ID]) ? $arg = $args[self::ARG_AGENT_ID] : $arg = NULL;
    return $arg;
  }
  
  /**
   * @return string ID of resource access is requested to.
   */
  public function getResourceId() {
    $args = $this->getArguments();
    isset($args[self::ARG_RESOURCE_ID]) ? $arg = $args[self::ARG_RESOURCE_ID] : $arg = NULL;
    return $arg;
  }
  
  /**
   * @return string ID of requested access level.
   */
  public function getConstraintId() {
    
    $args = $this->getArguments();
    isset($args[self::ARG_CONSTRAINT_ID]) ? $arg = $args[self::ARG_CONSTRAINT_ID] : $arg = NULL;
    return $arg;
  }
}