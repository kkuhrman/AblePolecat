<?php
/**
 * @file      polecat/ArgsList.php
 * @brief     Encapsulates an argument list passed to a function or class method.
 *
 * Pirates! Args.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'DynamicObject.php');

interface AblePolecat_ArgsListInterface extends AblePolecat_DynamicObjectInterface {
  
  /**
   * Returns assigned or default value and will not trigger an error.
   * 
   * @param string $name Name of given argument.
   * @param mixed $default Default value to return if not assigned.
   *
   * @return mixed Assigned value of argument given by $name if set, otherwise $default.
   */
  public function getArgumentValue($name, $default = NULL);
}

/**
 * Standard argument list.
 */
class AblePolecat_ArgsList extends AblePolecat_DynamicObjectAbstract implements AblePolecat_ArgsListInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_ArgsList();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_ArgsListInterface.
   ********************************************************************************/
  
  /**
   * Returns assigned or default value and will not trigger an error.
   * 
   * @param string $name Name of given argument.
   * @param mixed $default Default value to return if not assigned.
   *
   * @return mixed Assigned value of argument given by $name if set, otherwise $default.
   */
  public function getArgumentValue($name, $default = NULL) {
    return $this->getPropertyValue($name, $default);
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
  }
}