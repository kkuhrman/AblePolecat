<?php
/**
 * @file      polecat/core/Message/Request.php
 * @brief     Base class for all request messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 *
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Message.php');

interface AblePolecat_Message_RequestInterface extends AblePolecat_MessageInterface {
  
  const URI     = 'uri';
  
  /**
   * @return string Request method.
   */
  public function getMethod();
  
  /**
   * @return string Request resource (URI/URL).
   */
  public function getResource();
  
  /**
   * @todo: assign resource if building request to send to another server.
   */
}

abstract class AblePolecat_Message_RequestAbstract extends AblePolecat_MessageAbstract implements AblePolecat_Message_RequestInterface {
  
  /**
   * @var string Request resource (URI/URL).
   */
  private $m_resource;
  
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
    
    $ArgsList = AblePolecat_ArgsList::create();
    
    foreach($args as $key => $value) {
      switch ($method_name) {
        default:
          break;
        case 'create':
          switch($key) {
            case 0:
              $ArgsList->{AblePolecat_Message_RequestInterface::URI} = $value;
              break;
          }
          break;
      }
    }
    return $ArgsList;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Message_RequestInterface.
   ********************************************************************************/
  
  /**
   * @return string Request resource (URI/URL).
   */
  public function getResource() {
    return $this->m_resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @param string $resource (URI/URL).
   */
  protected function setResource($resource) {
    $this->m_resource = $resource;
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->m_resource = NULL;
  }
}