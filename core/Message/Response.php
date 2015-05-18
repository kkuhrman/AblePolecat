<?php
/**
 * @file      polecat/Message/Response.php
 * @brief     Base class for all response messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource.php')));

interface AblePolecat_Message_ResponseInterface extends AblePolecat_MessageInterface {
  
  const RESOURCE_ID             = 'resourceId';
  const STATUS_CODE             = 'statusCode';
  const REASON_PHRASE           = 'reason_phrase';
  const HEADER_FIELDS           = 'header_fields';
  const DOC_TYPE                = 'docType';
  const RESPONSE_REGISTRATION   = 'ResponseRegistration';
  
  /**
   * Some supported mime types.
   */
  const HEAD_CONTENT_TYPE_XML   = 'Content-type: text/xml; charset=utf-8';
  const HEAD_CONTENT_TYPE_HTML  = 'Content-type: text/html';
    
  /**
   * @return string Entity body as text.
   */
  public function getEntityBody();
  
  /**
   * @return string
   */
  public function getMimeType();
  
  /**
   * @return AblePolecat_ResourceInterface.
   */
  public function getResource();
  
  /**
   * @return string.
   */
  public function getResourceName();
  
  /**
   * @return string.
   */
  public function getResourceId();
  
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
   * @var object Singleton instance
   * @see getConcreteInstance(), setConcreteInstance().
   */
  private static $Response;
  
  /**
   * @var AblePolecat_ResourceInterface 
   */
  private $dataResource;
  
  /**
   * @var AblePolecat_Registry_Entry_DomNode_ResponseInterface
   */
  private $ResponseRegistration;
  
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
              if (is_numeric($value)) {
                $ArgsList->{AblePolecat_Message_ResponseInterface::STATUS_CODE} = $value;
              }
              else if (is_object($value) && is_a($value, 'AblePolecat_Registry_Entry_DomNode_ResponseInterface')) {
                $ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION} = $value;
              }
              break;
            case 1:
              $ArgsList->{AblePolecat_Message_ResponseInterface::HEADER_FIELDS} = $value;
              break;
          }
          break;
      }
    }
    if (isset(self::$Response)) {
      //
      // Assign properties from variable args list.
      //
      if (isset($ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION})) {
        self::$Response->ResponseRegistration = $ArgsList->{AblePolecat_Message_ResponseInterface::RESPONSE_REGISTRATION};
        self::$Response->setStatusCode(self::$Response->ResponseRegistration->getStatusCode());
        self::$Response->appendHeaderFields(self::$Response->ResponseRegistration->getDefaultHeaders());
      }
      else {
        self::$Response->setStatusCode(
          $ArgsList->getArgumentValue(AblePolecat_Message_ResponseInterface::STATUS_CODE, 200)
        );
        self::$Response->appendHeaderFields(
          $ArgsList->getArgumentValue(AblePolecat_Message_ResponseInterface::HEADER_FIELDS, array())
        );
      }
    }
    return $ArgsList;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Message_ResponseInterface.
   ********************************************************************************/
  
  /**
   * @return AblePolecat_ResourceInterface.
   */
  public function getResource() {
    return $this->dataResource;
  }
  
  /**
   * @return string.
   */
  public function getResourceName() {
    
    $resourceName = NULL;
    
    if (isset($this->ResponseRegistration)) {
      $resourceName = $this->ResponseRegistration->getName();
    }
    return $resourceName;
  }
  
  /**
   * @return string.
   */
  public function getResourceId() {
    
    $resourceId = NULL;
    
    if (isset($this->ResponseRegistration)) {
      $resourceId = $this->ResponseRegistration->getResourceId();
    }
    return $resourceId;
  }
  
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
     
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return DOMDocument contains the response.
   */
  protected function getDocument() {
    if (!isset($this->Document)) {
      throw new AblePolecat_Message_Exception('Attempt to dereference null DOM document object in response message.');
    }
    return $this->Document;
  }
  
  /**
   * @param DOMDocument $Document contains the response.
   */
  protected function setDocument(DOMDocument $Document) {
    $this->Document = $Document;
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
    echo $this->getEntityBody();
  }
  
  /**
   * @return AblePolecat_ResourceInterface.
   */
  protected function setResource(AblePolecat_ResourceInterface $Resource) {
    $this->dataResource = $Resource;
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
  protected function appendHeaderFields($fields = NULL) {
    if (isset($fields) && is_array($fields)) {
      foreach($fields as $key => $field) {
        if ($this->validateHeaderField($field)) {
          $this->headerFields[] = $field;
        }
      }
    }
  }
  
  /**
   * @return AblePolecat_Registry_Entry_DomNode_ResponseInterface.
   */
  protected function getResponseRegistration() {
    return $this->ResponseRegistration;
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
    $this->dataResource = NULL;
    $this->ResponseRegistration = NULL;
    $this->statusCode = 200;
    $this->reasonPhrase = '';
    $this->headerFields = array();
  }
  
  /**
   * Check if concrete instance of sub-class has been set.
   * 
   * Allows sub-classes to extend concrete (not abstract) classes overriding only which
   * sub-class is implemented in create().
   * 
   * @return Concrete instance of AblePolecat_Message_ResponseInterface or NULL
   */
  protected static function getConcreteInstance() {
    
    $Response = NULL;
    
    if (isset(self::$Response)) {
      $Response = self::$Response;
    }
    return $Response;
  }
   
  /**
   * Allows descendant sub-class to initialize private singleton member with its own concrete
   * instance whilst subsequent calls to same method by parent get ignored.
   *
   * @return AblePolecat_Message_ResponseInterface Instance of sub-class for further initialization.
   */
  protected static function setConcreteInstance(AblePolecat_Message_ResponseInterface $Response) {
    
    //
    // Has a descendant class already initialized singleton?
    //
    if (!isset(self::$Response)) {
      //
      // No. Allow initialization.
      //
      self::$Response = $Response;
    }
    return self::$Response;
  }
  
  /**
   * send HTTP headers.
   */
  final public function __destruct() {
  }
}