<?php
/**
 * @file: Command.php
 * Encapsulates synchronous interaction between two objects within scope of single script execution.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Command.php')));

interface AblePolecat_CommandInterface extends AblePolecat_AccessControl_ArticleInterface {
  
  /**
   * Return reference to object which invoked command.
   *
   * @return AblePolecat_AccessControl_SubjectInterface Object which invoked command.
   */
  public function getInvoker();
  
  /**
   * Invoke the command and return response from target.
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker Agent or role invoking command.
   *
   * @return mixed Result of command action.
   */
  public static function invoke(AblePolecat_AccessControl_SubjectInterface $Invoker);
}