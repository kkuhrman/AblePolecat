<?php
/**
 * @file      polecat/Message/Response/Xhtml.php
 * @brief     Base class for all response messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));

class AblePolecat_Message_Response_Xhtml extends AblePolecat_Message_ResponseAbstract {
  
  const HEAD_CONTENT_TYPE_HTML  = 'Content-type: text/html';
  const ELEMENT_HTML            = 'html';
  const ELEMENT_HEAD            = 'head';
  const ELEMENT_BODY            = 'body';
  
  /**
   * @var string.
   */
  private $namespaceUri;
  
  /**
   * @var string.
   */
  private $qualifiedName;
  
  /**
   * @var string.
   */
  private $publicId;
  
  /**
   * @var string.
   */
  private $systemId;
  
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
      $Response->namespaceUri = $ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION}->getNamespaceUri();
      $Response->qualifiedName = $ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION}->getQualifiedName();
      $Response->publicId = $ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION}->getPublicId();
      $Response->systemId = $ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION}->getSystemId();
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
    $Response = self::setConcreteInstance(new AblePolecat_Message_Response_Xhtml());
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
      $Document = AblePolecat_Dom::createDocument(
        AblePolecat_Dom::XHTML_1_1_NAMESPACE_URI,
        AblePolecat_Dom::XHTML_1_1_QUALIFIED_NAME,
        AblePolecat_Dom::XHTML_1_1_PUBLIC_ID,
        AblePolecat_Dom::XHTML_1_1_SYSTEM_ID
      );
      
      //
      // Creates empty <head> element.
      //
      $HeadElement = $Document->createElement(self::ELEMENT_HEAD);
      $DocumentHead = AblePolecat_Dom::appendChildToParent($HeadElement, $Document);
      // @todo: insert document title into head
      // $HeadContent = AblePolecat_Dom::getDocumentElementFromString($Resource->Head);
      // $HeadContent = AblePolecat_Dom::appendChildToParent($HeadContent, $Document, $DocumentHead);
      
      //
      // Create empty <body> element.
      //
      $BodyElement = $Document->createElement(self::ELEMENT_BODY);
      $DocumentBody = AblePolecat_Dom::appendChildToParent($BodyElement, $Document);
      $BodyContent = AblePolecat_Dom::getDocumentElementFromString($Resource->Body);
      $BodyContent = AblePolecat_Dom::appendChildToParent($BodyContent, $Document, $DocumentBody);
      $this->setDocument($Document);
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return string Entity body as text.
   */
  public function getEntityBody() {
    return $this->getDocument()->saveHTML();
  }
  
  /**
   * Send HTTP response headers.
   */
  protected function sendHead() {
    header(self::HEAD_CONTENT_TYPE_HTML);
    parent::sendHead();
  }
  
  /**
   * @return string.
   */
  public function getNamespaceUri() {
    return $this->namespaceUri;
  }
  
  /**
   * @return string.
   */
  public function getQualifiedName() {
    return $this->qualifiedName;
  }
  
  /**
   * @return string.
   */
  public function getPublicId() {
    return $this->publicId;
  }
  
  /**
   * @return string.
   */
  public function getSystemId() {
    return $this->systemId;
  }
}