<?php
/**
 * @file      polecat/Message/Response.php
 * @brief     Base class for all response messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Message.php');

interface AblePolecat_Message_ResponseInterface extends AblePolecat_MessageInterface {
  
  const STATUS_CODE             = 'status_code';
  const REASON_PHRASE           = 'reason_phrase';
  const HEADER_FIELDS           = 'header_fields';
  const RESOURCE_ID             = 'resource_id';
  
  const HEAD_CONTENT_TYPE_HTML  = 'Content-type: text/html';
  const HEAD_CONTENT_TYPE_JSON  = 'Content-Type: application/json';
  const HEAD_CONTENT_TYPE_XML   = 'Content-type: text/xml; charset=utf-8';
  
  const BODY_DOCTYPE_XML        = "<?xml version='1.0' standalone='yes'?>";
  
  /**
   * @return string The response status code.
   */
  public function getStatusCode();
  
  /**
   * @return string The response reason phrase.
   */
  public function getReasonPhrase();
  
  /**
   * Send HTTP Response.
   */
  public function send();
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
   * @var array Fields to be sent in the response header.
   */
  private $headerFields;
  
  /********************************************************************************
   * Implementation of AblePolecat_Message_ResponseInterface.
   ********************************************************************************/
  
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
  
  /**
   * Send HTTP Response.
   */
  public function send() {
    $this->sendHead();
    $this->sendBody();
  }
   
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Send HTTP response headers.
   */
  protected function sendHead() {
    //
    // @todo: send HTTP headers
    //
    if (count($this->headerFields)) {
      foreach($this->headerFields as $key => $field) {
        header($field);
      }
    }
    else {
      header(self::HEAD_CONTENT_TYPE_XML);
    }
    header('', TRUE, $this->getStatusCode());
  }
  
  /**
   * Send body of response.
   */
  protected function sendBody() {
    //
    // Echo response bodies (will note be sent before HTTP headers because
    // output buffer is not flushed until server goes out of scope).
    //
    if (isset($this->body)) {
      echo $this->body;
    }
  }
  
  /**
   * Initialize the status code.
   *
   * @param string $status_code.
   */
  protected function setStatusCode($status_code) {
    
    switch ($status_code) {
      default:
        $this->m_reason_phrase = NULL;
        break;
      case '100':
        $this->m_reason_phrase = 'Continue';
        break;
      case '101':
        $this->m_reason_phrase = 'Switching Protocols';
        break;
      case '200':
        $this->m_reason_phrase = 'OK';
        break;
      case '201':
        $this->m_reason_phrase = 'Created';
        break;
      case '202':
        $this->m_reason_phrase = 'Accepted';
        break;
      case '203':
        $this->m_reason_phrase = 'Non-Authoritative Information';
        break;
      case '204':
        $this->m_reason_phrase = 'No Content';
        break;
      case '205':
        $this->m_reason_phrase = 'Reset Content';
        break;
      case '206':
        $this->m_reason_phrase = 'Partial Content';
        break;
      case '300':
        $this->m_reason_phrase = 'Multiple Choices';
        break;
      case '301':
        $this->m_reason_phrase = 'Moved Permanently';
        break;
      case '302':
        $this->m_reason_phrase = 'Found';
        break;
      case '303':
        $this->m_reason_phrase = 'See Other';
        break;
      case '304':
        $this->m_reason_phrase = 'Not Modified';
        break;
      case '305':
        $this->m_reason_phrase = 'Use Proxy';
        break;
      case '307':
        $this->m_reason_phrase = 'Temporary Redirect';
        break;
      case '400':
        $this->m_reason_phrase = 'Bad Request';
        break;
      case '401':
        $this->m_reason_phrase = 'Unauthorized';
        break;
      case '402':
        $this->m_reason_phrase = 'Payment Required';
        break;
      case '403':
        $this->m_reason_phrase = 'Forbidden';
        break;
      case '404':
        $this->m_reason_phrase = 'Not Found';
        break;
      case '405':
        $this->m_reason_phrase = 'Method Not Allowed';
        break;
      case '406':
        $this->m_reason_phrase = 'Not Acceptable';
        break;
      case '407':
        $this->m_reason_phrase = 'Proxy Authentication Required';
        break;
      case '408':
        $this->m_reason_phrase = 'Request Time-out';
        break;
      case '409':
        $this->m_reason_phrase = 'Conflict';
        break;
      case '410':
        $this->m_reason_phrase = 'Gone';
        break;
      case '411':
        $this->m_reason_phrase = 'Length Required';
        break;
      case '412':
        $this->m_reason_phrase = 'Precondition Failed';
        break;
      case '413':
        $this->m_reason_phrase = 'Request Entity Too Large';
        break;
      case '414':
        $this->m_reason_phrase = 'Request-URI Too Large';
        break;
      case '415':
        $this->m_reason_phrase = 'Unsupported Media Type';
        break;
      case '416':
        $this->m_reason_phrase = 'Requested range not satisfiable';
        break;
      case '417':
        $this->m_reason_phrase = 'Expectation Failed';
        break;
      case '500':
        $this->m_reason_phrase = 'Internal Server Error';
        break;
      case '501':
        $this->m_reason_phrase = 'Not Implemented';
        break;
      case '502':
        $this->m_reason_phrase = 'Bad Gateway';
        break;
      case '503':
        $this->m_reason_phrase = 'Service Unavailable';
        break;
      case '504':
        $this->m_reason_phrase = 'Gateway Time-out';
        break;
      case '505':
        $this->m_reason_phrase = 'HTTP Version not supported';
        break;
    }
    if (isset($this->m_reason_phrase)) {
      $this->m_status_code = $status_code;
    }
    else {
      $this->m_status_code = NULL;
    }
  }
  
  /**
   * Append fields to be sent with response header.
   *
   * @param Array $fields.
   */
  protected function appendHeaderFields($fields) {
    if (isset($fields) && is_array($fields)) {
      foreach($fields as $key => $field) {
        if ($this->validateHeaderField($field)) {
          $this->headerFields[] = $field;
        }
      }
    }
  }
  
  /**
   * @todo: smirk
   */
  protected function validateHeaderField($field) {
    return TRUE;
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->m_status_code = 0;
    $this->m_reason_phrase = '';
    $this->headerFields = array();
    // AblePolecat_Error::INVALID_HTTP_RESPONSE
  }
  
  /**
   * send HTTP headers.
   */
  final public function __destruct() {
  }
}

class AblePolecat_Message_Response extends AblePolecat_Message_ResponseAbstract {
  
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
    $Response = new AblePolecat_Message_Response();
    
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    
    //
    // Assign properties from variable args list.
    //
    $Response->setStatusCode(
      $ArgsList->getArgumentValue(AblePolecat_Message_ResponseInterface::STATUS_CODE, 200)
    );
    $Response->appendHeaderFields(
      $ArgsList->getArgumentValue(AblePolecat_Message_ResponseInterface::HEADER_FIELDS, array())
    );
    
    //
    // Return initialized object.
    //
    return $Response;
  }
  
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
              $ArgsList->{AblePolecat_Message_ResponseInterface::STATUS_CODE} = $value;
              break;
            case 1:
              $ArgsList->{AblePolecat_Message_ResponseInterface::HEADER_FIELDS} = $value;
              break;
          }
          break;
      }
    }
    return $ArgsList;
  }
}