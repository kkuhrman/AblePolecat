<?php
/**
 * @file      polecat/Message/Response.php
 * @brief     Base class for all response messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource.php')));

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
   * DOM element tag names.
   */
  const DOM_ELEMENT_TAG_ROOT    = 'AblePolecat';
  
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
  
  /**
   * @param AblePolecat_ResourceInterface $Resource
   */
  public function setEntityBody(AblePolecat_ResourceInterface $Resource);
}

abstract class AblePolecat_Message_ResponseAbstract extends AblePolecat_MessageAbstract implements AblePolecat_Message_ResponseInterface {
  
  /**
   * @var DOMDocument contains the response.
   */
  private $Document;
  
  /**
   * @var string The response status code (e.g. HTTP example would be 200).
   */
  private $statusCode;
  
  /**
   * @var string The response reason phrase (e.g. HTTP example would be 'OK').
   */
  private $reasonPhrase;
  
  /**
   * @var array Fields to be sent in the response header.
   */
  private $headerFields;
  
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
  
  /********************************************************************************
   * Implementation of AblePolecat_Message_ResponseInterface.
   ********************************************************************************/
  
  /**
   * @return string The response status code.
   */
  public function getStatusCode() {
    return $this->statusCode;
  }
  
  /**
   * @return string The response reason phrase.
   */
  public function getReasonPhrase() {
    return $this->reasonPhrase;
  }
  
  /**
   * Send HTTP Response.
   */
  public function send() {
    $this->sendHead();
    $this->sendBody();
  }
  
  /**
   * @param AblePolecat_ResourceInterface $Resource
   */
  public function setEntityBody(AblePolecat_ResourceInterface $Resource) {
    
    if (!isset($this->Document)) {
      $this->Document = AblePolecat_Dom::createXmlDocument(self::DOM_ELEMENT_TAG_ROOT);
      $parentElement = $this->Document->firstChild;
      $Element = $Resource->getDomNode($this->Document);
      $Element = AblePolecat_Dom::appendChildToParent($Element, $this->Document, $parentElement);
    }
    else {
      throw new AblePolecat_Message_Exception(sprintf("Entity body for response [%s] has already been set.", $this->getName()));
    }
  }
   
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return DOMDocument contains the response.
   */
  protected function getDocument() {
    if (!isset($this->Document)) {
      $this->Document = AblePolecat_Dom::createXmlDocument(self::DOM_ELEMENT_TAG_ROOT);
    }
    return $this->Document;
  }
  
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
    // Echo response bodies (will not be sent before HTTP headers because
    // output buffer is not flushed until server goes out of scope).
    //
    // if (isset($this->body)) {
      // echo $this->body;
    // }
    echo $this->getDocument()->saveXML();
  }
  
  /**
   * Initialize the status code.
   *
   * @param string $status_code.
   */
  protected function setStatusCode($status_code) {
    
    switch ($status_code) {
      default:
        $this->reasonPhrase = NULL;
        break;
      case '100':
        $this->reasonPhrase = 'Continue';
        break;
      case '101':
        $this->reasonPhrase = 'Switching Protocols';
        break;
      case '200':
        $this->reasonPhrase = 'OK';
        break;
      case '201':
        $this->reasonPhrase = 'Created';
        break;
      case '202':
        $this->reasonPhrase = 'Accepted';
        break;
      case '203':
        $this->reasonPhrase = 'Non-Authoritative Information';
        break;
      case '204':
        $this->reasonPhrase = 'No Content';
        break;
      case '205':
        $this->reasonPhrase = 'Reset Content';
        break;
      case '206':
        $this->reasonPhrase = 'Partial Content';
        break;
      case '300':
        $this->reasonPhrase = 'Multiple Choices';
        break;
      case '301':
        $this->reasonPhrase = 'Moved Permanently';
        break;
      case '302':
        $this->reasonPhrase = 'Found';
        break;
      case '303':
        $this->reasonPhrase = 'See Other';
        break;
      case '304':
        $this->reasonPhrase = 'Not Modified';
        break;
      case '305':
        $this->reasonPhrase = 'Use Proxy';
        break;
      case '307':
        $this->reasonPhrase = 'Temporary Redirect';
        break;
      case '400':
        $this->reasonPhrase = 'Bad Request';
        break;
      case '401':
        $this->reasonPhrase = 'Unauthorized';
        break;
      case '402':
        $this->reasonPhrase = 'Payment Required';
        break;
      case '403':
        $this->reasonPhrase = 'Forbidden';
        break;
      case '404':
        $this->reasonPhrase = 'Not Found';
        break;
      case '405':
        $this->reasonPhrase = 'Method Not Allowed';
        break;
      case '406':
        $this->reasonPhrase = 'Not Acceptable';
        break;
      case '407':
        $this->reasonPhrase = 'Proxy Authentication Required';
        break;
      case '408':
        $this->reasonPhrase = 'Request Time-out';
        break;
      case '409':
        $this->reasonPhrase = 'Conflict';
        break;
      case '410':
        $this->reasonPhrase = 'Gone';
        break;
      case '411':
        $this->reasonPhrase = 'Length Required';
        break;
      case '412':
        $this->reasonPhrase = 'Precondition Failed';
        break;
      case '413':
        $this->reasonPhrase = 'Request Entity Too Large';
        break;
      case '414':
        $this->reasonPhrase = 'Request-URI Too Large';
        break;
      case '415':
        $this->reasonPhrase = 'Unsupported Media Type';
        break;
      case '416':
        $this->reasonPhrase = 'Requested range not satisfiable';
        break;
      case '417':
        $this->reasonPhrase = 'Expectation Failed';
        break;
      case '500':
        $this->reasonPhrase = 'Internal Server Error';
        break;
      case '501':
        $this->reasonPhrase = 'Not Implemented';
        break;
      case '502':
        $this->reasonPhrase = 'Bad Gateway';
        break;
      case '503':
        $this->reasonPhrase = 'Service Unavailable';
        break;
      case '504':
        $this->reasonPhrase = 'Gateway Time-out';
        break;
      case '505':
        $this->reasonPhrase = 'HTTP Version not supported';
        break;
    }
    if (isset($this->reasonPhrase)) {
      $this->statusCode = $status_code;
    }
    else {
      $this->statusCode = NULL;
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
    $this->Document = NULL;
    $this->statusCode = 200;
    $this->reasonPhrase = '';
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
}