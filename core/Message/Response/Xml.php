<?php
/**
 * @file      polecat/Message/Response/Xml.php
 * @brief     Base class for all response messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));

class AblePolecat_Message_Response_Xml extends AblePolecat_Message_ResponseAbstract {
  
  const HEAD_CONTENT_TYPE_XML   = 'Content-type: text/xml; charset=utf-8';
  const BODY_DOCTYPE_XML        = "<?xml version='1.0' standalone='yes'?>";
  
  /**
   * DOM element tag names.
   */
  const DOM_ELEMENT_TAG_ROOT    = 'AblePolecat';
  
  /**
   * @var object Singleton instance
   */
  // private static $Response;
  
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
    // Check if this sub-class extends another.
    //
    $Response = self::getConcreteInstance();
    if (!isset($Response)) {
      //
      // Create concrete instance of response sub-class.
      //
      $Response = new AblePolecat_Message_Response_Xml();
      self::setConcreteInstance($Response);
      
      //
      // Unmarshall (from numeric keyed index to named properties) variable args list.
      //
      $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    }
    
    //
    // Return initialized object.
    //
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
      $Document = AblePolecat_Dom::createXmlDocument(self::DOM_ELEMENT_TAG_ROOT);
      $parentElement = $Document->firstChild;
      $Element = $Resource->getDomNode($Document);
      $Element = AblePolecat_Dom::appendChildToParent($Element, $Document, $parentElement);
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
    header(self::HEAD_CONTENT_TYPE_XML);
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
    echo $this->getDocument()->saveXML();
  }
}