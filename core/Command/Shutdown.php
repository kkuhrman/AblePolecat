<?php
/**
 * @file      polecat/Command/Shutdown.php
 * @brief     Retrieve user access agent in scope for command invoker.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Reverse.php')));

class AblePolecat_Command_Shutdown extends AblePolecat_Command_ReverseAbstract {
  
  const UUID = '7ca0f570-1f22-11e4-8c21-0800200c9a66';
  const NAME = 'Shutdown';
  
  const ARG_REASON    = 'reason';
  const ARG_MESSAGE   = 'message';
  const ARG_STATUS    = 'status';
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
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
    $reason = self::checkArgument($name, $VarArgs, 1, 'string');
    $message = self::checkArgument($name, $VarArgs, 2, 'string');
    isset($VarArgs[3]) ? $status = self::checkArgument($name, $VarArgs, 3, 'integer') : $status = 0;
    $CommandArguments = array(
      self::ARG_REASON  => $reason,
      self::ARG_MESSAGE => $message,
      self::ARG_STATUS  => $status,
    );
    
    //
    // Create and dispatch command
    //
    $Command = new AblePolecat_Command_Shutdown($Invoker, $CommandArguments);
    return $Command->dispatch();
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * @return string Shutdown reason.
   */
  public function getReason() {
    
    $args = $this->getArguments();
    isset($args[self::ARG_REASON]) ? $arg = $args[self::ARG_REASON] : $arg = NULL;
    return $arg;
  }
  
  /**
   * @return string Shutdown message.
   */
  public function getMessage() {
    $args = $this->getArguments();
    isset($args[self::ARG_MESSAGE]) ? $arg = $args[self::ARG_MESSAGE] : $arg = NULL;
    return $arg;
  }
  
  /**
   * @return string Shutdown return code.
   */
  public function getStatus() {
    $args = $this->getArguments();
    isset($args[self::ARG_STATUS]) ? $arg = $args[self::ARG_STATUS] : $arg = NULL;
    return $arg;
  }
}