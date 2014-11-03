<?php
/**
 * @file      polecat/Message/Response/Xhtml.php
 * @brief     Base class for all response messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));

class AblePolecat_Message_Response_Xhtml extends AblePolecat_Message_ResponseAbstract {
  
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
  
  /**
   * @var Array String substitutions.
   */
  private $entityBodyStringSubstitutes;
  
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
   * @return string
   */
  public function getMimeType() {
    return self::HEAD_CONTENT_TYPE_HTML;
  }
  
  /**
   * @return string Entity body as text.
   */
  public function getEntityBody() {
    return $this->postProcessEntityBody($this->getDocument()->saveHTML());
  }
  
  /**
   * @param AblePolecat_ResourceInterface $Resource
   */
  public function setEntityBody(AblePolecat_ResourceInterface $Resource) {
    
    try {
      $Document = $this->getDocument();
      throw new AblePolecat_Message_Exception(sprintf("Entity body for response [%s] has already been set.", $Resource->getName()));
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
      
      //
      // Treat all scalar Resource properties as potential substitution strings.
      //
      // $this->setDefaultSubstitutionMarkers($Resource);
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Post processing of entity body is final edit. Typically simple text substitutions.
   *
   * This function is called after DOM document has been set and rendered as text.
   *
   * @param $string entityBody
   *
   * @return string.
   */
  protected function postProcessEntityBody($entityBody) {
    $substitutionMarkers = array_keys($this->entityBodyStringSubstitutes);
    $substitutionValue = $this->entityBodyStringSubstitutes;
    $entityBody = str_replace($substitutionMarkers, $substitutionValue, $entityBody);
    return $entityBody;
  }
  
  /**
   * Preprocessing DOM document allows sub-classes to insert/append additional elements.
   * 
   * This function is called after DOM document has been created but before it is set.
   *
   * @param DOMDocument $Document
   *
   * @return DOMDocument $Document
   */
  protected function preprocessEntityBody(DOMDocument $Document) {
    return $Document;
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
  
  /**
   * Allows users to replace text formatted as {!sometext} in templates with given value.
   *
   * @param string $substitutionMarker Uniquely identifies a substitution marker.
   * @param string $substitutionValue The text, which will replace the substitution marker.
   * @param string $encoding Necessary if substitution string is to be used in URLs
   *
   * NOTE: This is method is destructive. It will overwrite previous value if already set.
   * However, it will make note of such overwrites in [log].
   */
  public function setSubstitutionMarker($substitutionMarker, $substitutionValue, $encoding = 'domurl') {
    //
    // @todo: this is preg hell; following expression returns 1 if last character is NOT '}'
    //
    $matches = array();
    $result = preg_match_all("{![0-9a-zA-Z._]}", $substitutionMarker, $matches);
    if ($result && is_scalar($substitutionValue)) {
      switch ($encoding) {
        default:
          break;
        case 'RFC3986':
          //
          // @todo: some characters ({,}) get encoded,others do not (!)
          //
          $substitutionMarker = rawurlencode($substitutionMarker);
          $substitutionValue = rawurlencode($substitutionValue);
          break;
        case 'domurl':
          $substitutionMarker = str_replace(array('{', '}'), array('%7B', '%7D'), $substitutionMarker);
          // $substitutionValue = urlencode($substitutionValue);
          break;
      }
      if (isset($this->entityBodyStringSubstitutes[$substitutionMarker]) && ($this->entityBodyStringSubstitutes[$substitutionMarker] != strval($substitutionValue))) {
        AblePolecat_Command_Log::invoke(
          AblePolecat_Host::getUserAgent(), 
          sprintf("substitution marker %s value = %s replaced with %s.", $substitutionMarker, $substitutionValue), 
          'info'
        );
        $this->entityBodyStringSubstitutes[$substitutionMarker] = strval($substitutionValue);
      }
      else {
        $this->entityBodyStringSubstitutes[$substitutionMarker] = strval($substitutionValue);
      }
    }
    else {
      AblePolecat_Command_Log::invoke(
        AblePolecat_Host::getUserAgent(), 
        sprintf("substitution marker %s (value = %s) is not valid. proper syntax is {![0-9a-zA-Z._]}.", $substitutionMarker, $substitutionValue), 
        'info'
      );
    }
  }
  
  /**
   * Treats all scalar Resource properties as potential substitution strings.
   */
  protected function setDefaultSubstitutionMarkers(AblePolecat_ResourceInterface $Resource) {
    $property = $Resource->getFirstProperty();
    while($property) {
      if (is_a($property, 'AblePolecat_Data_ScalarInterface')) {
        $substitutionMarker = sprintf("{!%s}", $property->getPropertyKey());
        $substitutionValue = sprintf("%s", $property);
        $this->setSubstitutionMarker($substitutionMarker, $substitutionValue);
      }
    }
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->entityBodyStringSubstitutes = array();
  }
}