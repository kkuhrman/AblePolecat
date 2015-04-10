<?php
/**
 * @file      Command/Server.php
 * @brief     Encapsulates a command to AblePolecat_Mode_Server.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command.php')));

interface AblePolecat_Command_ServerInterface extends AblePolecat_CommandInterface {
}

abstract class AblePolecat_Command_ServerAbstract 
  extends AblePolecat_CommandAbstract 
  implements AblePolecat_Command_ServerInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_CommandInterface.
   ********************************************************************************/
   
  /**
   * Indicates which direction to pass command along CoR.
   */
  public static function direction() {
    return AblePolecat_Command_TargetInterface::CMD_LINK_REV;
  }
}