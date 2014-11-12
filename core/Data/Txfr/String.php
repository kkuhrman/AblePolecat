<?php
/**
 * @file: String.php
 * Standard string transformation class.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Txfr.php')));

class AblePolecat_Data_Txfr_String extends AblePolecat_Data_TxfrAbstract {
  
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    //
    // transformation support class
    // @todo: verify all core classes loadable by convention.
    //
    // $ClassRegistry = NULL;
    // $CommandResult = AblePolecat_Command_GetRegistry::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), 'AblePolecat_Registry_Class');
    // if ($CommandResult->success()) {
      // $ClassRegistry = $CommandResult->value();
    // }
    // if (isset($ClassRegistry) && !$ClassRegistry->isLoadable('AblePolecat_Data_Scalar_String')) {
      // $ClassRegistry->registerLoadableClass(
        // 'AblePolecat_Data_Scalar_String', 
        // implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar', 'String.php')),
        // 'typeCast'
      // );
    // }
  }
  
  /**
   * Remove illegal characters from input and save to output.
   *
   * @return mixed Output as sanitized version of raw input.
   */
  public function sanitize() {
    $output = AblePolecat_Data_Scalar_String::typeCast($this->input);
    $this->output = filter_var($output, FILTER_SANITIZE_STRING);
    return $this->output;
  }
  
  /**
   * Perform data transformation on input and save to output.
   * 
   * @return mixed Output as transformed version of sanitized, raw input.
   */
  public function transform() {
    $this->sanitize();
    return $this->output;
  }
}