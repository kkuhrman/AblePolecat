<?php
/**
 * @file: Overloaded.php
 * Order and names of arguments passed in variable list are known before runtime.
 */

include(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'ArgsList.php');

/**
 * Overloaded argument list.
 */
class AblePolecat_ArgsList_Overloaded extends AblePolecat_ArgsListAbstract {
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
  }
    
  /**
   * Creational method.
   *
   * @param First parameter (if any) is expected to be associative array with argument NVP.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    $ArgsList = new AblePolecat_ArgsList_Overloaded();
    $parameters = func_get_args();
    if (isset($parameters[0])) {
      $variable_args_list = $parameters[0];
      foreach($variable_args_list as $arg_name => $arg_value) {
        $ArgsList->$arg_name = $arg_value;
      }
    }
    return $ArgsList;
  }
}