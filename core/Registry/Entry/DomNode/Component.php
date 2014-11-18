<?php
/**
 * @file      polecat/core/Registry/Entry/DomNode/Component.php
 * @brief     Encapsulates record of a component registered in [component].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode.php')));

interface AblePolecat_Registry_Entry_ComponentInterface extends AblePolecat_Registry_Entry_DomNodeInterface {
  
  /**
   * @return string.
   */
  public function getComponentId();
  
  /**
   * @return int.
   */
  public function getComponentClassName();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_DomNode_Component extends AblePolecat_Registry_Entry_DomNodeAbstract implements AblePolecat_Registry_Entry_ComponentInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_DomNode_Component();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ComponentInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getComponentId() {
    return $this->getPropertyValue('componentId');
  }
  
  /**
   * @return string.
   */
  public function getComponentClassName() {
    return $this->getPropertyValue('componentClassName');
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
    parent::initialize();
  }
}