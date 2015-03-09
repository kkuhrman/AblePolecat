<?php
/**
 * @file      polecat/core/Component.php
 * @brief     A specialized DOM element sub-class for not-scalar resource properties.
 * 
 * The Component class provides a solution for merging not-scalar resource properties into 
 * template elements. Not-scalar properties include data structures such as lists and tables. 
 * In order to support late-binding of these data structures with presentation syntax in 
 * a template, the Component class allows designer to express a presentation syntax for a 
 * single table row,list item , etc. in the template, which will be extracted by the Component 
 * class at runtime and used as a micro template for embedding the data structure into the 
 * rendered document.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom', 'Element.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode', 'Component.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource.php')));

interface AblePolecat_ComponentInterface 
  extends AblePolecat_Dom_ElementInterface,
          AblePolecat_AccessControl_Article_StaticInterface {
  /**
   * Create an instance of component initialized with given resource data.
   *
   * @param AblePolecat_ResourceInterface $Resource.
   *
   * @return AblePolecat_ComponentInterface.
   */
  public static function create(AblePolecat_ResourceInterface $Resource);
}

abstract class AblePolecat_ComponentAbstract 
  implements AblePolecat_ComponentInterface {
  
  /**
   * AblePolecat_ResourceInterface $Resource.
   */
  private $dataResource;
  
  /**
   * @var string.
   */
  private $tagName;
  
  /**
   * @var DOMElement Component template from file as DOM element.
   */
  private $DomElement;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Scope of operation.
   *
   * @return string.
   */
  public static function getScope() {
    return 'APPLICATION';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Dom_ElementInterface.
   ********************************************************************************/
  
  /**
   * @return string Tag name of element.
   */
  public function getTagName() {
    return $this->tagName;
  }
  
  /**
   * @param string $tagName.
   */
  public function setTagName($tagName) {
    $this->tagName = $tagName;
  }
  
  /********************************************************************************
   * Constructor and initialization functions.
   ********************************************************************************/
  
  /**
   * @return DOMElement Component template from file as DOM element.
   */
  protected function getTemplateElement() {
    if (!isset($this->DomElement)) {
      //
      // Component registration.
      //
      $ComponentRegistration = AblePolecat_Registry_Entry_DomNode_Component::fetch(array($this->getId()));
      
      //
      // Template registration.
      //
      isset($ComponentRegistration) ? $articleId = $ComponentRegistration->getId() : $articleId = '';
      $TemplateRegistrations = AblePolecat_Registry_Template::getRegistrationsByArticleId($articleId);
      isset($TemplateRegistrations[0]) ? $TemplateRegistration = $TemplateRegistrations[0] : $TemplateRegistration = NULL;
      
      //
      // DOMElement.
      //
      isset($TemplateRegistration) ? $templateFullPath = $TemplateRegistration->getFullPath() : $templateFullPath = NULL;
      if (isset($templateFullPath)) {
        $this->DomElement = AblePolecat_Dom::getDocumentElementFromFile($templateFullPath);
      }
    }
    return $this->DomElement;
  }
  /**
   * @return AblePolecat_ResourceInterface $Resource.
   */
  protected function getResource() {
    return $this->dataResource;
  }
  
  /**
   * @param AblePolecat_ResourceInterface $Resource.
   */
  protected function setResource(AblePolecat_ResourceInterface $Resource) {
    $this->dataResource = $Resource;
  }
  
  /**
   * Extends __construct().
   */
  abstract protected function initialize();
  
  /**
   * @see: initialize().
   */
  final protected function __construct() {
    $this->dataResource = NULL;
    $this->DomElement = NULL;
    $this->tagName = NULL;
    $this->initialize();
  }
}