<?php
/**
 * @file      Command/Log.php
 * @brief     Execute SQL on core/application database.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Reverse.php')));

class AblePolecat_Command_Log extends AblePolecat_Command_ReverseAbstract {
  
  const UUID = '85fc7590-724d-11e3-981f-0800200c9a66';
  const NAME = 'Log';
  
  const ARG_EVENT_MSG = 'eventMessage';
  const ARG_EVENT_SEV = 'eventSeverity';
  const ARG_LOG_NAME  = 'logName';
  
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
   * @param string $eventMessage Event message.
   * @param string $eventSeverity Event severity code.
   * @param string $logName Name of log if not default (optional).
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
    $eventMessage = self::checkArgument($name, $VarArgs, 1, 'string');
    $eventSeverity = self::checkArgument($name, $VarArgs, 2, 'string');
    isset($VarArgs[3]) ? $logName = self::checkArgument($name, $VarArgs, 3, 'string') : $logName = 'default';
    $Event = array(
      self::ARG_EVENT_MSG => $eventMessage,
      self::ARG_EVENT_SEV => $eventSeverity,
      self::ARG_LOG_NAME  => $logName,
    );
    
    //
    // Create and dispatch command
    //
    $Command = new AblePolecat_Command_Log($Invoker, $Event);
    return $Command->dispatch();
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * @return string Event message.
   */
  public function getEventMessage() {
    
    $args = $this->getArguments();
    isset($args[self::ARG_EVENT_MSG]) ? $arg = $args[self::ARG_EVENT_MSG] : $arg = NULL;
    return $arg;
  }
  
  /**
   * @return string Event severity.
   */
  public function getEventSeverity() {
    $args = $this->getArguments();
    isset($args[self::ARG_EVENT_SEV]) ? $arg = $args[self::ARG_EVENT_SEV] : $arg = NULL;
    return $arg;
  }
  
  /**
   * @return string Name of event log.
   */
  public function getLogName() {
    $args = $this->getArguments();
    isset($args[self::ARG_LOG_NAME]) ? $arg = $args[self::ARG_LOG_NAME] : $arg = NULL;
    return $arg;
  }
}