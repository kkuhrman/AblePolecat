<?php
/**
 * @file      polecat/core/Message/Request.php
 * @brief     Base class for all request messages in Able Polecat.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 *
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource', 'Locater.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Message.php');

interface AblePolecat_Message_RequestInterface extends AblePolecat_MessageInterface {
  
  const URI                 = 'uri';
  const URI_PATH            = 'path';
  const URI_RESOURCE_NAME   = 'resource_name'; // In most cases, relative path.
  const URI_REDIRECT        = 'uri_redirect'; // TRUE if resolved path is not the original request, otherwise FALSE.
  const URI_SEARCH_PARAM    = 'q'; // Parameter in query string identifying string to send to search feature.
  
  /**
   * Core (built-in) resource names (e.g. 'reserved').
   */
  const RESOURCE_NAME_ACK       = 'ack'; // ping
  const RESOURCE_NAME_HOME      = 'home'; // Home page
  const RESOURCE_NAME_ERROR     = 'error'; // extended error information
  const RESOURCE_NAME_FORM      = 'form'; // basic, built-in UI form
  const RESOURCE_NAME_INSTALL   = 'install'; // interactive install
  const RESOURCE_NAME_SEARCH    = 'search'; // list view of search results
  const RESOURCE_NAME_TEST      = 'test'; // run local unit tests
  const RESOURCE_NAME_UPDATE    = 'update'; // update project configuration
  const RESOURCE_NAME_UTIL      = 'util'; // Project utilities
  
  /**
   * @return string
   */
  public function getHostName();
  
  /**
   * @return string Request method.
   */
  public function getMethod();
  
  /**
   * @return string Request resource (URI/URL).
   */
  public function getResource();
  
  /**
   * Return info relating to analysis of URI request path.
   * 
   * @return Array.
   */
  public function getRequestPathInfo();
  
  /**
   * Return the path part of request URI.
   *
   * @param bool $asString If TRUE return path as string, otherwise return parts in array.
   * 
   * @return mixed return type depends on value of $asString
   */
  public function getRequestPath($asString = TRUE);
  
  /**
   * Return the query string part of request URI.
   *
   * @param bool $asString If TRUE return query string as URL encoded string, otherwise return parts in array.
   * 
   * @return mixed return type depends on value of $asString
   */
  public function getRequestQueryString($asString = TRUE);
  
  /**
   * @return string Base URL (host name[/alias]).
   */
  public function getBaseUrl($trailing_slash = TRUE);
  
  /**
   * @return mixed ID corresponding to record of raw request in database.
   */
  public function getRawRequestLogRecordId();
  
  /**
   * @return mixed Redirect URL or FALSE.
   */
  public function getRedirectUrl();
  
  /**
   * Check resource name against list of allowable characters etc.
   *
   * @param string $requestedResourceName Name of requested resource.
   *
   * @return string Sanitized/normalized version of resource name.
   */
  public function validateResourceName($requestedResourceName);
  
  /**
   * @todo: assign resource if building request to send to another server.
   */
}

abstract class AblePolecat_Message_RequestAbstract extends AblePolecat_MessageAbstract implements AblePolecat_Message_RequestInterface {
  
  /**
   * @var string Name of host/vhost (typically $_SERVER['HTTP_HOST']).
   */
  private $hostName;
  
  /**
   * @var string Request resource (URI/URL).
   */
  private $resourceUri;
  
  /**
   * @var Array Analysis of request URI.
   */
  private $request_path_info;
  
  /**
   * @var string Alias part of host name.
   */
  private $alias;
   
  /**
   * @var string protocol://host_name/[alias].
   */
  private $host_url;
    
  /**
   * @var string Part of request URI after host/alias and before query string.
   */
  private $request_path;
  
  /**
   * @var Array Entity body aka document.
   */
  private $entity_body;
  
  /**
   * @var Array Query string arguments.
   */
  private $query_string;
  
  /**
   * @var mixed ID of raw request log entry in database.
   */
  private $rawRequestLogRecordId;
  
  /**
   * @var mixed Redirect URL or FALSE.
   */
  private $redirectUrl;
  
  /**
   * @var boolean Internal flag set TRUE if request is for core (built-in) resource.
   */
  private $requestResourceIsCore;
  
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
   * Return info relating to analysis of URI request path.
   * 
   * @return Array.
   */
  public function getRequestPathInfo() {
    return $this->request_path_info;
  }
  
  /**
   * Return the path part of request URI.
   *
   * @param bool $asString If TRUE return path as string, otherwise return parts in array.
   * 
   * @return mixed return type depends on value of $asString
   */
  public function getRequestPath($asString = TRUE) {
    
    $asString ? $path = implode(AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, $this->request_path) : $path = $this->request_path;
    return $path;
  }
  
  /**
   * Return the query string part of request URI.
   *
   * @param bool $asString If TRUE return query string as URL encoded string, otherwise return parts in array.
   * 
   * @return mixed return type depends on value of $asString
   */
  public function getRequestQueryString($asString = TRUE) {
    
    $query_string = $this->query_string;
    if ($asString) {
      $query_string = array();
      foreach($this->query_string as $parameter => $args) {
        $query_string[] = sprintf("%s=%s", $parameter, rawurlencode(implode('+', $args)));
      }
      $query_string = implode('&', $query_string);          
    }
    return $query_string;
  }
  
  /**
   * @return string Base URL (host name[/alias]).
   */
  public function getBaseUrl($trailing_slash = TRUE) {
    
    $trailing_slash ? $host_url = $this->host_url : $host_url = rtrim($this->host_url, AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH);
    return $host_url;
  }
  
  /**
   * @return mixed ID corresponding to record of raw request in database.
   */
  public function getRawRequestLogRecordId() {
    return $this->rawRequestLogRecordId;
  }
  
  /**
   * @return mixed Redirect URL or FALSE.
   */
  public function getRedirectUrl() {
    return $this->redirectUrl;
  }
  
  /**
   * Check resource name against list of allowable characters etc.
   *
   * @param string $requestedResourceName Name of requested resource.
   *
   * @return string Sanitized/normalized version of resource name.
   */
  public function validateResourceName($requestedResourceName) {
    //
    // @todo: URI security
    //
    $resolvedResourceName = FALSE;
    $sanitizedResourceName = strtolower(preg_replace(array('/[^\/\a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', '-', ''), $requestedResourceName));
    $uriPathParts = explode(AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, $sanitizedResourceName);
    
    //
    // First, check for core resource request.
    //
    if (is_array($uriPathParts) && isset($uriPathParts[0])) {
      switch($uriPathParts[0]) {
        default:
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_ACK:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_ERROR:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_SEARCH:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_TEST:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_UPDATE:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_UTIL:
          $resolvedResourceName = $uriPathParts[0];
          break;
      }
    }
    
    //
    // Otherwise, check sanitized resource names against registry.
    //
    if ($resolvedResourceName === FALSE) {
      $sql = __SQL()->
        select('name')->
        from('resource')->
        where(sprintf("`name` = '%s' AND `hostName` = '%s'", $sanitizedResourceName, $this->getHostName()));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), $sql);
      if ($CommandResult->success() && count($CommandResult->value())) {
        $resourceName = $CommandResult->value();
        $resolvedResourceName = $resourceName[0]['name'];
        $this->requestResourceIsCore = FALSE;
      }
      else {
        $message = sprintf("Request for unregistered resource %s/%s",
          $this->getHostName(),
          $sanitizedResourceName
        );
        AblePolecat_Command_Log::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), $message, AblePolecat_LogInterface::STATUS);
      }
    }
    return $resolvedResourceName;
  }
  
  /**
   * @return string
   */
  public function getHostName() {
    return $this->hostName;
  }
  
  /**
   * @return string Request resource (URI/URL).
   */
  public function getResource() {
    return $this->resourceUri;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Return value of query string field if it exists.
   *
   * @param string $fieldName Name of query string field (parameter).
   *
   * @return mixed Value of given query string field or NULL.
   */
  public function getQueryStringFieldValue($fieldName) {
    
    $fieldValue = NULL;
    if (isset($this->query_string[$fieldName])) {
      $fieldValue = $this->query_string[$fieldName];
      if (is_array($fieldValue) && (1 === count($fieldValue))) {
        $fieldValue = $fieldValue[0];
      }
    }
    return $fieldValue;
  }
  
  /**
   * @param mixed $rawRequestLogRecordId
   */
  public function setRawRequestLogRecordId($rawRequestLogRecordId) {
    $this->rawRequestLogRecordId = $rawRequestLogRecordId;
  }
  
  /**
   * Break off path from host name and analyze components.
   */
  protected function analyzeRequestPath() {
    
    $this->request_path_info = array();
    
    isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = 'GET';
    
    //
    // Remove trailing slash, alias and query string, if any, from request URI
    //
    isset($this->alias) ? $alias = $this->alias : $alias = '';
    isset($_SERVER['REQUEST_URI']) ? $request_uri = $_SERVER['REQUEST_URI'] : $request_uri = '';
    isset($_SERVER['QUERY_STRING']) ? $query_string = $_SERVER['QUERY_STRING'] : $query_string = '';
    $this->request_path_info[self::URI_PATH] = trim(str_replace($alias, '', str_replace($query_string, '', $request_uri)), AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH . '?');
    $this->request_path = explode(AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, $this->request_path_info[self::URI_PATH]);
    
    //
    // Is there anything left of the path?
    //
    if (isset($this->request_path[0]) && ($this->request_path[0] != '')) {
      //
      // Is it a request for a recognized resource on the system?
      //
      $resourceName = $this->validateResourceName($this->request_path_info[self::URI_PATH]);
      if ($resourceName) {
        //
        // Yes, a valid resource name was given.
        //
        $this->request_path_info[self::URI_RESOURCE_NAME] = $resourceName;
        $this->request_path_info[self::URI_REDIRECT] = FALSE;
      }
      else {
        //
        // @todo: log error in event request is for script, CSS etc that does not exist at given path.
        //
        
        //
        // No, an invalid resource name was given; redirect to search.
        //
        $this->request_path_info[self::URI_RESOURCE_NAME] = self::RESOURCE_NAME_SEARCH;
        $this->request_path_info[self::URI_REDIRECT] = TRUE;
        
        //
        // Build search query string from invalid resource path.
        //
        $query_string = $this->makeRequestQueryString(array(self::URI_SEARCH_PARAM => $this->request_path));
        $this->redirectUrl = sprintf("%s?%s",
          implode(AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, array($this->host_url, self::RESOURCE_NAME_SEARCH)),
          $query_string
        );
      }
    }
    else {
      //
      // Otherwise, this is a request for the home page.
      //
      $this->request_path_info[self::URI_RESOURCE_NAME] = self::RESOURCE_NAME_HOME;
      $this->request_path_info[self::URI_REDIRECT] = FALSE;
    }
        
    switch ($method) {
      default:
        break;
      case 'POST':
        if (isset($this->entity_body[self::URI_SEARCH_PARAM])) {
          //
          // POST process search form results, redirect to search page.
          //
          $this->request_path_info[self::URI_RESOURCE_NAME] = self::RESOURCE_NAME_SEARCH;
          $this->request_path_info[self::URI_REDIRECT] = TRUE;
          $query_string = $this->makeRequestQueryString(array(self::URI_SEARCH_PARAM => $this->entity_body[self::URI_SEARCH_PARAM]));
          $this->redirectUrl = sprintf("%s?%s",
            implode(AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, array($this->host_url, self::RESOURCE_NAME_SEARCH)),
            $query_string
          );
        }        
    }
  }
  
  /**
   * Filter (sanitize) entity body if sent (e.g. POST data).
   */
  protected function filterEntityBody() {
    
    if (isset($_POST) && count($_POST)) {
      //
      // Is the search parameter present in the POST data or query string?
      //
      if (isset($_POST[self::URI_SEARCH_PARAM])) {
        //
        // Sanitize raw form data
        //
        $body = strtolower(addslashes($_POST[self::URI_SEARCH_PARAM]));
        
        //
        // Remove unwanted characters
        //
        $body = preg_replace("[^ 0-9a-zA-Z]", " ", $body);
        
        //
        // Remove multiple adjacent spaces
        //
        while (strstr($body, "  ")) {
          $body = str_replace("  ", " ", $body);
        }
        
        //
        // Break up search parameter string into array of search terms
        //
        $this->entity_body[self::URI_SEARCH_PARAM] = explode(' ', $body);
      }
      else {
        $this->entity_body = $_POST;
      }
    }
  }
  
  /**
   * Filter (sanitize) query string if sent.
   */
  protected function filterQueryString() {
    
    $this->query_string = array();

    foreach($_REQUEST as $argName => $argValue) {
      //
      // Deal with array passed in query string.
      //
      if (is_array($argValue)) {
        $argName = strtolower(addslashes($argName));
        $this->query_string[$argName] = $argValue;
      }
      else {
        //
        // Sanitize
        //
        $argName = strtolower(addslashes($argName));
        $argValue = strtolower(addslashes($argValue));
        
        //
        // Store
        //
        $this->query_string[$argName] = explode('+', $argValue);
      }
    }
  }
  
  /**
   * Initialize URL of host (or virtual host).
   */
  protected function initializeHostUrl() {
    //
    // HTTP | HTTPS
    //
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
      $protocol .= 's';
    }
    
    //
    // @todo: this order works on Win 7Pro and CENTOS - not tested otherwise
    // @todo: requests from behind firewall or where hosts file resolves to private IP?
    //
    $host = '';
    if(isset($_SERVER['HTTP_HOST'])) {
      $host = $_SERVER['HTTP_HOST'];
    }
    else if(isset($_SERVER['SERVER_NAME'])) {
      $host = $_SERVER['SERVER_NAME'];
    }
    
    //
    // Alias after host name?
    // Defined in ./etc/polecat/conf/path.config. Typically for local development environments.
    //
    if (defined('ABLE_POLECAT_ALIAS')) {
      if (ABLE_POLECAT_ALIAS == AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH) {
        //
        // This can cause problems with string manipulation. Unset alias if it is only '/'
        //
        $this->alias = NULL;
      }
      else {
        //
        // Make sure first character of given alias is a leading slash.
        //
        $given_alias = ABLE_POLECAT_ALIAS;
        $pos = strpos(AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, ABLE_POLECAT_ALIAS);
        if ($pos !== 0) {
          $given_alias = AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH . ABLE_POLECAT_ALIAS;
        }
          
        //
        // Validate given alias name against PHP globals
        // Eliminate OS-dependent path separators.
        //
        $document_root = str_replace(DIRECTORY_SEPARATOR, '&#47;', ABLE_POLECAT_DOCROOT);
        $script_filename = str_replace(AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, '&#47;', $_SERVER['SCRIPT_FILENAME']);
        $request_path = str_replace($document_root, '', $script_filename);
        $script_name = str_replace(AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, '&#47;', $_SERVER['SCRIPT_NAME']);
        $alias = str_replace('&#47;', AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH, str_replace($request_path, '', $script_name));
        if ($alias === $given_alias) {
          $this->alias = $given_alias;
        }
        else {
          trigger_error(sprintf("Check defined alias in ./etc/polecat/conf/path.config. Given: %s; Derived: %s.",
              ABLE_POLECAT_ALIAS,
              $alias
            ), 
            E_USER_ERROR
          );
        }
      }
    }
    else {
      $this->alias = NULL;
    }    
    
    //
    // base URL protocol://host[/alias]
    // 
    $this->hostName = $host;
    $this->host_url = "$protocol://$host";
    
    //
    // Add any alias
    //
    if (isset($this->alias)) {
      $this->hostName .=  $this->alias;
      $this->host_url .=  $this->alias;
    }
    else {
      $this->host_url .= AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH;
    }
    $this->hostName = trim($this->hostName, AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH);
  }
  
  /**
   * Generate a URI request query string from an array of parameter NVP.
   *
   * @param Array $parameters Array[name => value(s)]
   * 
   * @return string
   */
  protected function makeRequestQueryString($parameters) {
    
    $query_string_parts = array();
    $query_string = '';
    
    if (isset($parameters) && is_array($parameters)) {
      foreach($parameters as $parameter => $args) {
        is_scalar($args) ? $args = array($args) : NULL;
        $query_string_parts[] = sprintf("%s=%s", strval($parameter), rawurlencode(implode('+', $args)));
      }
      $query_string = implode('&', $query_string_parts);
    }
    return $query_string;
  }
  
  /**
   * @param string $resource (URI/URL).
   */
  protected function setResource($resource = NULL) {
    
    if (!isset($resource)) {
      //
      // Resource URI is not specified. Use current HTTP request.
      // Allocate array in any case - container for invalid path parts
      // @see getRequestPathInfo()
      //
      $this->entity_body = array();
      
      //
      // aka 'base' URL.
      //
      $this->initializeHostUrl();
      
      //
      // Filter (sanitize) entity body if sent (e.g. POST data).
      //
      $this->filterEntityBody();
      
      //
      // Filter (sanitize) query string if sent.
      //
      $this->filterQueryString();
      
      //
      // Break off path from host name and analyze components.
      //
      $this->analyzeRequestPath();
      
      //
      // Initialize full resource URI from incoming request.
      //
      $this->resourceUri = $this->getBaseUrl() . AblePolecat_AccessControl_Resource_LocaterInterface::URI_SLASH . $this->getRequestPath(TRUE);
    }
    else {
      //
      // Resource URI is specified.
      // @todo: ensure $this->request_path_info[self::URI_RESOURCE_NAME] excludes host name.
      //
      $url_parts = parse_url($resource);
      isset($url_parts['scheme']) ? $protocol = $url_parts['scheme'] . "://" : $protocol = '';
      isset($url_parts['host']) ? $host = $url_parts['host'] : $host = '';
      $this->hostName = $host;
      $this->host_url = $protocol . $host;
      isset($url_parts['host']) ? $this->request_path = $url_parts['host'] : $this->request_path = '';
      
      $this->resourceUri = $resource;
      $this->request_path_info = array();
      $this->request_path_info[self::URI_RESOURCE_NAME] = $resource;
      $this->request_path_info[self::URI_REDIRECT] = TRUE;
    }
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->hostName = NULL;
    $this->resourceUri = NULL;
    $this->request_path_info = NULL;
    $this->rawRequestLogRecordId = NULL;
    $this->redirectUrl = FALSE;
    
    //
    // By default, all requests are for a core resource unless a registry
    // entry for URI path is retrieved.
    //
    $this->requestResourceIsCore = TRUE;
  }
  
  /**
   * send HTTP headers.
   */
  final public function __destruct() {
  }
}