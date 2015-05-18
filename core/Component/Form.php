<?php
/**
 * @file      polecat/core/Component/Form.php
 * @brief     Encapsulates basic web form.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Component.php')));

class AblePolecat_Component_Form extends AblePolecat_ComponentAbstract {
  
  const UUID = 'f0d65d62-f277-11e4-b9b2-0050569e00a2';
  const NAME = 'AblePolecat_Component_Form';
  
  /********************************************************************************
   * Implementation of AblePolecat_Data_PrimitiveInterface.
   ********************************************************************************/
  
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL) {
    //
    // Create a temporary DOM document from template.
    //
    $templateElement = AblePolecat_Dom::appendChildToParent($this->getTemplateElement(), $Document);
    return $templateElement;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_ComponentInterface.
   ********************************************************************************/
  
  /**
   * Create an instance of component initialized with given resource data.
   *
   * @param AblePolecat_Registry_Entry_ComponentInterface $ComponentRegistration
   * @param AblePolecat_ResourceInterface $Resource.
   *
   * @return AblePolecat_ComponentInterface.
   */
  public static function create(
    AblePolecat_Registry_Entry_ComponentInterface $ComponentRegistration,
    AblePolecat_ResourceInterface $Resource
  ) {
    $Component = new AblePolecat_Component_Form($ComponentRegistration, $Resource);
    return $Component;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return Data expressed as a string.
   */
  public function __toString() {
    //
    // @todo: output element as text
    //
    return '';
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->setTagName('div');
    $this->getTemplateElement();
  }
}