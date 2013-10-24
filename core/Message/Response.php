<?php
/**
 * @file: Response.php
 * Base class for all response messages in Able Polecat.
 */

include(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Overloadable.php');
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Message.php');

interface AblePolecat_Message_ResponseInterface extends AblePolecat_MessageInterface, AblePolecat_OverloadableInterface {
  
  const STATUS_CODE   = 'status_code';
  const REASON_PHRASE = 'reason_phrase';
}

abstract class AblePolecat_Message_ResponseAbstract extends AblePolecat_MessageAbstract implements AblePolecat_Message_ResponseInterface {
  
  /**
   * @var string The response status code (e.g. HTTP example would be 200).
   */
  private $m_status_code;
  
  /**
   * @var string The response reason phrase (e.g. HTTP example would be 'OK').
   */
  private $m_reason_phrase;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->m_status_code = 0;
    $this->m_reason_phrase = '';
    // AblePolecat_Error::INVALID_HTTP_RESPONSE
  }
  
  /**
   * Initialize the status code.
   *
   * @param string $status_code.
   */
  protected function setStatusCode($status_code) {
    $this->m_status_code = $status_code;
  }
  
  /**
   * Initialize the reason phrase.
   *
   * @param string $reason_phrase.
   */
  protected function setReasonPhrase($reason_phrase) {
    $this->m_reason_phrase = $reason_phrase;
  }
  
  /**
   * @return string The response status code.
   */
  public function getStatusCode() {
    return $this->m_status_code;
  }
  
  /**
   * @return string The response reason phrase.
   */
  public function getReasonPhrase() {
    return $this->m_reason_phrase;
  }
}

class AblePolecat_Message_Response extends AblePolecat_Message_ResponseAbstract {
  
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
              $ArgsList->{AblePolecat_Message_ResponseInterface::STATUS_CODE} = $value;
              break;
            case 1:
              $ArgsList->{AblePolecat_Message_ResponseInterface::REASON_PHRASE} = $value;
              break;
          }
          break;
      }
    }
    return $ArgsList;
  }
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create() {
    
    //
    // Create a new response object.
    //
    $Response = new AblePolecat_Message_Response();
    
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    
    //
    // Assign properties from variable args list.
    //
    $Response->setStatusCode(
      $ArgsList->getPropertySafe(AblePolecat_Message_ResponseInterface::STATUS_CODE, 200)
    );
    $Response->setReasonPhrase(
      $ArgsList->getPropertySafe(AblePolecat_Message_ResponseInterface::REASON_PHRASE, 'OK')
    );
    
    //
    // Return initialized object.
    //
    return $Response;
  }
}