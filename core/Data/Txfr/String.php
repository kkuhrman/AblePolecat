<?php
/**
 * @file: String.php
 * Standard string transformation class.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Data', 'Txfr.php')));

class AblePolecat_Data_Txfr_String extends AblePolecat_Data_TxfrAbstract {
  
  /**
   * Extends __construct(). 
   */
  protected function initialize() {
    //
    // transformation support class
    //
    if (!AblePolecat_Server::getClassRegistry()->isLoadable('AblePolecat_Data_Scalar_String')) {
      AblePolecat_Server::getClassRegistry()->registerLoadableClass(
        'AblePolecat_Data_Scalar_String', 
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Data', 'Scalar', 'String.php')),
        'typeCast'
      );
    }
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