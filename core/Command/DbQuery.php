<?php
/**
 * @file: Command/DbQuery.php
 * Execute SQL on core/application database.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Reverse.php')));

class AblePolecat_Command_DbQuery extends AblePolecat_Command_ReverseAbstract {
  
  const UUID = 'ef797050-715c-11e3-981f-0800200c9a66';
  const NAME = 'DbQuery';
     
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
  
  /**
   * Invoke the command and return response from target.
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker Agent or role invoking command.
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $SQL SQL statement.
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
    $sql = self::checkArgument(self::getName(), func_get_args(), 1, 'object', 'AblePolecat_QueryLanguage_Statement_Sql_Interface');
    
    //
    // Create and dispatch command
    //
    $Command = new AblePolecat_Command_DbQuery($Invoker, $sql);
    return $Command->dispatch();
  }
}