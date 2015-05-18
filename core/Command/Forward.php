<?php
/**
 * @file      Command/Forward.php
 * @brief     Encapsulates a command, which is passed from highest ranking to lowest target along CoR.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command.php')));

interface AblePolecat_Command_ForwardInterface extends AblePolecat_CommandInterface {
  const DIRECTION = 'forward';
}

abstract class AblePolecat_Command_ForwardAbstract extends AblePolecat_CommandAbstract implements AblePolecat_Command_ForwardInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_CommandInterface.
   ********************************************************************************/
   
  /**
   * Indicates which direction to pass command along CoR.
   */
  public static function direction() {
    return self::DIRECTION;
  }
}