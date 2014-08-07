<?php
/**
 * @file      polecat/core/Message/Request/Delete.php
 * @brief     Encapsulates an Able Polecat DELETE request.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request.php')));

class AblePolecat_Message_Request_Delete extends AblePolecat_Message_RequestAbstract {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
   
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {
    
    $Request = new AblePolecat_Message_Request_Delete();
    
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    
    $Request->setResource(
      $ArgsList->getArgumentValue(
        AblePolecat_Message_RequestInterface::URI, 
        URI_SLASH
      )
    );
    
    $Request->setHead(
      $ArgsList->getArgumentValue(
        AblePolecat_Message_RequestInterface::HEAD, 
        ''
      )
    );
    $Request->setBody(
      $ArgsList->getArgumentValue(
        AblePolecat_Message_RequestInterface::ENTITY_BODY, 
        ''
      )
    );
    
    return $Request;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Message_RequestInterface.
   ********************************************************************************/
  
  /**
   * @return string Request method.
   */
  public function getMethod() {
    return 'DELETE';
  }
}