<?php
/**
 * @file      polecat/Message/Response/Xml.php
 * @brief     Base class for all response messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));

class AblePolecat_Message_Response_Xml extends AblePolecat_Message_ResponseAbstract {
  
  /**
   * Registry article constants.
   */
  const UUID = '3dcd0564-b7af-11e4-a12d-0050569e00a2';
  const NAME = 'AblePolecat_Message_Response_Xml';
  
  const BODY_DOCTYPE_XML        = "<?xml version='1.0' standalone='yes'?>";
  
  /**
   * DOM element tag names.
   */
  const DOM_ELEMENT_TAG_ROOT    = 'AblePolecat';
    
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {    
    self::setConcreteInstance(new AblePolecat_Message_Response_Xml());
    self::unmarshallArgsList(__FUNCTION__, func_get_args());
    return self::getConcreteInstance();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Message_ResponseInterface.
   ********************************************************************************/
  
  /**
   * @return string
   */
  public function getMimeType() {
    return self::HEAD_CONTENT_TYPE_XML;
  }
  
  /**
   * @return string Entity body as text.
   */
  public function getEntityBody() {
    return $this->getDocument()->saveXML();
  }
  
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
      
      //
      // Stash raw resource.
      //
      $this->setResource($Resource);
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
}