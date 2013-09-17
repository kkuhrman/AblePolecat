<?php
/**
 * @file: Mode.php
 * Analogous to OS mode (protected etc) except defines context of web app.
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'CacheObject.php')));

interface AblePolecat_Mode extends AblePolecat_CacheObjectInterface {
  
  /**
   * @return AblePolecat_EnvironmentInterface.
   */
  public function getEnvironment();
  
  /**
   * @return AblePolecat_Mode if encapsulated mode is ready for work, otherwise NULL.
   */
  public static function ready();
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
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}