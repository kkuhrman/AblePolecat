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
   * DOM element tag names.
   */
  const DOM_ELEMENT_TAG_ROOT    = 'AblePolecat';
  
  /**
   * @var object Singleton instance
   */
  private static $Response;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {
    
    //
    // Create a new response object.
    //
    self::$Response = new AblePolecat_Message_Response_Xhtml();
    
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    
    //
    // Assign properties from variable args list.
    //
    self::$Response->setStatusCode(
      $ArgsList->getArgumentValue(AblePolecat_Message_ResponseInterface::STATUS_CODE, 200)
    );
    self::$Response->appendHeaderFields(
      $ArgsList->getArgumentValue(AblePolecat_Message_ResponseInterface::HEADER_FIELDS, array())
    );
    
    //
    // Return initialized object.
    //
    return self::$Response;
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
   * Send HTTP response headers.
   */
  protected function sendHead() {
    header(self::HEAD_CONTENT_TYPE_HTML);
    parent::sendHead();
  }
  
  /**
   * Send body of response.
   */
  protected function sendBody() {
    //
    // Echo response bodies (will not be sent before HTTP headers because
    // output buffer is not flushed until server goes out of scope).
    //
    echo $this->getDocument()->saveHTML();
  }
}