<?php
/**
 * @file: Txfr.php 
 * Interface and abstract base class for all data transformation sub-classes.
 */

interface AblePolecat_Data_TxfrInterface {
  
  /**
   * Alias of transform().
   * @see transform().
   */
  public function getOutput();
  
  /**
   * Rollback any transformation operations on output.
   */
  public function reset();
  
  /**
   * Remove illegal characters from input and save to output.
   *
   * @return mixed Output as sanitized version of raw input.
   */
  public function sanitize();
  
  /**
   * Perform data transformation on input and save to output.
   * 
   * @return mixed Output as transformed version of sanitized, raw input.
   */
  public function transform();
}

abstract class AblePolecat_Data_TxfrAbstract implements AblePolecat_Data_TxfrInterface {
  
  /**
   * @var mixed Transformed data output.
   */
  protected $output;
  
  /**
   * @var mixed Raw input from data source.
   */
  public $rawInput;
  
  /**
   * Extends __construct(). 
   * 
   * Sub-classes register transformation support class(es) here etc.
   */
  abstract protected function initialize();
  
  /**
   * Alias of transform().
   * @see transform().
   */
  public function getOutput() {
    return $this->transform();
  }
  
  /**
   * Rollback any transformation operations on output.
   */
  public function reset() {
    $this->output = NULL;
  }
  
  /**
   * Cannot override Constructor.
   */
  final public function __construct($input = NULL) {
    $this->input = $input;
    $this->output = NULL;
    $this->initialize();
  }
}