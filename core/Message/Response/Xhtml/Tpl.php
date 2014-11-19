<?php
/**
 * @file      polecat/Message/Response/Xhtml/Tpl.php
 * @brief     XHTML response based on template saved in file.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode', 'Component.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response', 'Xhtml.php')));

class AblePolecat_Message_Response_Xhtml_Tpl extends AblePolecat_Message_Response_Xhtml {
  
  /**
   * @var string.
   */
  private $templateFullPath;
  
  /********************************************************************************
   * Implementation of AblePolecat_OverloadableInterface.
   ********************************************************************************/
  
  /**
   * Marshall numeric-indexed array of variable method arguments.
   *
   * @param string $method_name __METHOD__ is good enough.
   * @param Array $args Variable list of arguments passed to method (i.e. get_func_args()).
   * @param mixed $options Reserved for future use.
   *
   * @return Array Associative array representing [argument name] => [argument value]
   */
  public static function unmarshallArgsList($method_name, $args, $options = NULL) {
    
    $ArgsList = parent::unmarshallArgsList($method_name, $args, $options);
    $Response = self::getConcreteInstance();
    if (isset($Response) && isset($ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION})) {
      $Response->templateFullPath = $ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION}->getTemplateFullPath();
    }
    return $ArgsList;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {    
    $Response = self::setConcreteInstance(new AblePolecat_Message_Response_Xhtml_Tpl());
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    return self::getConcreteInstance();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Message_ResponseInterface.
   ********************************************************************************/
  
  /**
   * @param AblePolecat_ResourceInterface $Resource
   */
  public function setEntityBody(AblePolecat_ResourceInterface $Resource) {
    
    try {
      $Document = $this->getDocument();
      throw new AblePolecat_Message_Exception(sprintf("Entity body for response [%s] has already been set.", $this->getName()));
    }
    catch(AblePolecat_Message_Exception $Exception) {
      
      //
      // Treat all scalar Resource properties as potential substitution strings.
      //
      $this->setDefaultSubstitutionMarkers($Resource);
      
      //
      // Stash raw resource.
      //
      $this->setResource($Resource);
      
      //
      // Create DOM document.
      //
      $Document = NULL;
      $templateFullPath = $this->getTemplateFullPath();
      if (isset($templateFullPath) && file_exists($templateFullPath)) {
        $Document = AblePolecat_Dom::createDocumentFromTemplate($templateFullPath);
      }
      else {
        $Document = AblePolecat_Dom::createDocument(
          AblePolecat_Dom::XHTML_1_1_NAMESPACE_URI,
          AblePolecat_Dom::XHTML_1_1_QUALIFIED_NAME,
          AblePolecat_Dom::XHTML_1_1_PUBLIC_ID,
          AblePolecat_Dom::XHTML_1_1_SYSTEM_ID
        );
      }
      if (isset($Document)) {
        $Document = $this->preprocessEntityBody($Document);
        $this->setDocument($Document);
      }
    }
  }
  
  /**
   * @return string Full path to file containing document template.
   */
  public function getTemplateFullPath() {
    return $this->templateFullPath;
  }
  
  /**
   * @param string $templateFullPath Full path to file containing document template.
   */
  public function setTemplateFullPath($templateFullPath) {
    try {
      $Document = $this->getDocument();
      throw new AblePolecat_Message_Exception(sprintf("Cannot set full path to template file [%s] after DOM document has been loaded.", $this->getName()));
    }
    catch(AblePolecat_Message_Exception $Exception) {
      $this->templateFullPath = $templateFullPath;
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
}