<?php
/**
 * @file      polecat/Message/Response/Cached.php
 * @brief     Encapsulates a response stored in [cache].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Cache.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));

class AblePolecat_Message_Response_Cached extends AblePolecat_Message_ResponseAbstract {
  
  /**
   * @var AblePolecat_Registry_Entry_Cache
   */
  private $CacheRegistration;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {    
    self::setConcreteInstance(new AblePolecat_Message_Response_Cached());
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
    
    $mimeType = NULL;
    if (isset($this->CacheRegistration)) {
      $mimeType = $this->CacheRegistration->getMimeType();
    }
    return $mimeType;
  }
  
  /**
   * @return string Entity body as text.
   */
  public function getEntityBody() {
    
    $EntityBody = NULL;
    if (isset($this->CacheRegistration)) {
      $EntityBody = $this->CacheRegistration->cacheData;
    }
    return $EntityBody;
  }
  
  /**
   * @param AblePolecat_ResourceInterface $Resource
   */
  public function setEntityBody(AblePolecat_ResourceInterface $Resource) {
    AblePolecat_Command_Log::invoke(
      AblePolecat_AccessControl_Agent_User::wakeup(), 
      sprintf("%s passed to %s, which is a non-functional stub. @see setCachedResponse().", AblePolecat_Data::getDataTypeName($Resource), __METHOD__), 
      'info'
    );
  }
  
  /**
   * @param AblePolecat_Registry_Entry_Cache $CacheRegistration
   */
  public function setCachedResponse(AblePolecat_Registry_Entry_Cache $CacheRegistration) {
    $this->CacheRegistration = $CacheRegistration;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Send HTTP response headers.
   */
  protected function sendHead() {
    header($this->getMimeType());
    parent::sendHead();
  }
}