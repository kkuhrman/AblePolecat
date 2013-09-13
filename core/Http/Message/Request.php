<?php
/**
 * @file: Request.php
 * Encapsulates an HTTP request.
 *
 * NOTE: Other than create(), static methods operate on incoming REQUEST as
 * encapsulated in PHP super globals $_SERVER, $_REQUEST, etc. Otherwise,
 * concrete methods are used to initialize an outgoing request.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Http', 'Message.php')));
include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Request.php')));

interface AblePolecat_Http_Message_RequestInterface extends AblePolecat_Http_MessageInterface {}

class AblePolecat_Http_Message_Request extends AblePolecat_Message_RequestAbstract implements AblePolecat_Http_Message_RequestInterface {
  
  /**
   * @var string Request method.
   */
  private $method;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // @todo: really?
    //
    $this->method = 'GET';
  }
  
  /**
   * Create a concrete instance of AblePolecat_MessageInterface.
   *
   * @param Array $head Optional message header fields (NVP).
   * @param mixed $body Optional message body.
   *
   * @return AblePolecat_MessageInterface Concrete instance of message or NULL.
   */
  public static function create($head = NULL, $body = NULL) {
    
    $Request = new AblePolecat_Http_Message_Request();
    $Request->setHead($head);
    $Request->setBody($body);
    return $Request;
  }
  
  /**
   * @return string Request method.
   */
  public function getMethod() {
    $this->method;
  }
  
  /**
   * Get value of given query string variable.
   *
   * @param string $var Name of requested query string variable.
   *
   * @return mixed Value of requested variable or NULL.
   */
  public static function getVariable($var) {
    $value = NULL;
    if (isset($var) && isset($_REQUEST[$var])) {
      $value = $_REQUEST[$var];
    }
    return $value;
  }
  
  /**
   * At present, should return HTTP/1.1.
   */
  public function getVersion() {
    return "HTTP/1.1";
  }
}