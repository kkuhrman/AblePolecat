<?php
/**
 * @file: Command/Reverse.php
 * Encapsulates a command, which is passed from lowest ranking to highest target along CoR.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command.php')));

interface AblePolecat_Command_ReverseInterface extends AblePolecat_CommandInterface {
  const DIRECTION = 'reverse';
}

abstract class AblePolecat_Command_ReverseAbstract extends AblePolecat_CommandAbstract implements AblePolecat_Command_ReverseInterface {
  
  /**
   * Indicates which direction to pass command along CoR.
   */
  public static function direction() {
    return self::DIRECTION;
  }
}