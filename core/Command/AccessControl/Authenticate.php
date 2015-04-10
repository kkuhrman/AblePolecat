<?php
/**
 * @file      Command/AccessControl/Authenticate.php
 * @brief     Authenticate user credentials with an authority.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Reverse.php')));

class AblePolecat_Command_AccessControl_Authenticate extends AblePolecat_Command_ReverseAbstract {
  
  const UUID = '80fe3992-44e7-11e4-b353-0050569e00a2';
  const NAME = 'Authenticate';
  
  const ARG_USER = 'userName';
  const ARG_PASS = 'password';
  const ARG_AUTHORITY  = 'authority';
  
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
   * @param string $userName user name.
   * @param string $password user password.
   * @param string $authority ID of authenticating authority.
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
    $userName = self::checkArgument($name, $VarArgs, 1, 'string');
    $password = self::checkArgument($name, $VarArgs, 2, 'string');
    isset($VarArgs[3]) ? $authority = self::checkArgument($name, $VarArgs, 3, 'string') : $authority = NULL;
    $AuthenticationRequest = array(
      self::ARG_USER => $userName,
      self::ARG_PASS => $password,
      self::ARG_AUTHORITY  => $authority,
    );
    
    //
    // Create and dispatch command
    //
    $Command = new AblePolecat_Command_AccessControl_Authenticate($Invoker, $AuthenticationRequest);
    return $Command->dispatch();
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * @return string User Name.
   */
  public function getUserName() {
    
    $args = $this->getArguments();
    isset($args[self::ARG_USER]) ? $arg = $args[self::ARG_USER] : $arg = NULL;
    return $arg;
  }
  
  /**
   * @return string Password.
   */
  public function getPassword() {
    $args = $this->getArguments();
    isset($args[self::ARG_PASS]) ? $arg = $args[self::ARG_PASS] : $arg = NULL;
    return $arg;
  }
  
  /**
   * @return string ID of authenticating authority.
   */
  public function getAuthority() {
    $args = $this->getArguments();
    isset($args[self::ARG_AUTHORITY]) ? $arg = $args[self::ARG_AUTHORITY] : $arg = NULL;
    return $arg;
  }
}