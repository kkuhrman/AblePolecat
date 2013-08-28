<?php
/**
 * @file: Mode.php
 * Analogous to OS mode (protected etc) except defines context of web app.
 */

interface AblePolecat_Mode {
  
  /**
   * @return AblePolecat_EnvironmentInterface.
   */
  public function getEnvironment();
  
  /**
   * @return AblePolecat_Mode if encapsulated mode is ready for work, otherwise NULL.
   */
  public static function ready();
  
  /**
   * Initialize and return object implementing AblePolecat_Mode.
   */
  public static function wakeup();
  
  /**
   * Persist state prior to going out of scope.
   */
  public function sleep();
}

abstract class AblePolecat_ModeAbstract implements AblePolecat_Mode {
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  protected $Environment;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    $this->Environment = NULL;
  }
  
  /**
   * @return AblePolecat_EnvironmentInterface.
   */
  public function getEnvironment() {
    return $this->Environment;
  }
    
  /**
   * @see: start();
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  /**
   * @see: sleep();
   */
  final public function __destruct() {
    $this->sleep();
  }
}