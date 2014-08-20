<?php
/**
 * @file      polecat/core/Resource/Resource_Registration.php
 * @brief     Encapsulates an argument list passed to a function or class method.
 *
 * Pirates! Args.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'DynamicObject.php');

interface AblePolecat_Resource_RegistrationInterface extends AblePolecat_DynamicObjectInterface {
  /**
   * @return string.
   */
  public function getResourceName();
  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return string.
   */
  public function getResourceClassName();
  
  /**
   * @return string.
   */
  public function getResourceAuthorityClassName();
  
  /**
   * @return int.
   */
  public function getResourceDenyCode();
}

/**
 * Standard argument list.
 */
class AblePolecat_Resource_Registration extends AblePolecat_DynamicObjectAbstract implements AblePolecat_Resource_RegistrationInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Resource_Registration();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Resource_RegistrationInterface.
   ********************************************************************************/
  
  /**
   * @return string.
   */
  public function getResourceName() {
    return $this->getPropertyValue('resourceName');
  }
  
  /**
   * @return string.
   */
  public function getResourceId() {
    return $this->getPropertyValue('resourceId');
  }
  
  /**
   * @return string.
   */
  public function getResourceClassName() {
    return $this->getPropertyValue('resourceClassName');
  }
  
  /**
   * @return string.
   */
  public function getResourceAuthorityClassName() {
    return $this->getPropertyValue('resourceAuthorityClassName');
  }
  
  /**
   * @return int.
   */
  public function getResourceDenyCode() {
    return $this->getPropertyValue('resourceDenyCode');
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