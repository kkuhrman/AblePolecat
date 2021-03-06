<?php
/**
 * @file      polecat/Command/GetRegistry.php
 * @brief     Retrieve a registry object.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Reverse.php')));

class AblePolecat_Command_GetRegistry extends AblePolecat_Command_ReverseAbstract {
  
  const UUID = 'c7587ad0-74a4-11e3-981f-0800200c9a66';
  const NAME = 'GetRegistry';
  
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
   * @param string $className The class name of the requested registry.
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
    $className = self::checkArgument(self::getName(), func_get_args(), 1, 'string');
    
    //
    // Create and dispatch command
    //
    $Command = new AblePolecat_Command_GetRegistry($Invoker, $className);
    return $Command->dispatch();
  }
}