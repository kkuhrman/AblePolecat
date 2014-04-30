<?php
/**
 * @file: polecat/Host.php
 * Base class for [virtual] computer host acting as either client or server (request/response).
 */

if (!defined('URI_SLASH')) {
  $URI_SLASH = chr(0x2F);
  define('URI_SLASH', $URI_SLASH);
}
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Delegate', 'System.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Host.php')));

interface AblePolecat_HostInterface {
  /**
   * Main point of entry for all Able Polecat page and service requests.
   *
   */
  public static function routeRequest();
  
  /**
   * Checks if the requested resource is a valid name in the system.
   *
   * @param string $requestedResourceName Name of requested resource.
   *
   * @return string Name of valid resource (default is 'search').
   */
  public function validateResourceName($requestedResourceName);
}

abstract class AblePolecat_HostAbstract extends AblePolecat_AccessControl_Delegate_SystemAbstract implements AblePolecat_HostInterface {
  
  const URI_SLASH           = URI_SLASH;
  const URI_PATH            = 'path';
  const URI_RESOURCE_NAME   = 'resource_name'; // Name of object type (e.g. product, location, etc).
  const URI_REDIRECT        = 'uri_redirect'; // TRUE if resolved path is not the original request, otherwise FALSE.
  const URI_SEARCH_PARAM    = 'q'; // Parameter in query string identifying string to send to search feature.
  
  const RESOURCE_NAME_HOME       = 'home'; // Home page
  const RESOURCE_NAME_SEARCH     = 'search'; // list view of search results
  const RESOURCE_NAME_UTIL       = 'util'; // Project utilities (install, update, more...)
  
  /**
   * @var Instance of concrete singleton class.
   */
  protected static $Host = NULL;
  
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
   * @var Array Analysis of request URI.
   */
  private $request_path_info;
  
  /**
   * @var Array Entity body aka document.
   */
  private $entity_body;
  
  /**
   * @var Array Query string arguments.
   */
  private $query_string;
  
  /**
   * Return info relating to analysis of URI request path.
   * 
   * @return Array.
   */
  public static function getRequestPathInfo() {
    
    $request_path_info = NULL;
          
    if (isset(self::$Host)) {
        $request_path_info = self::$Host->request_path_info;
    }
    else {
      throw new AblePolecat_Host_Exception('Host is not initialized and failed to return request path info.');
    }
    return $request_path_info;
  }
  
  /**
   * Return the path part of request URI.
   *
   * @param bool $asString If TRUE return path as string, otherwise return parts in array.
   * 
   * @return mixed return type depends on value of $asString
   */
  public static function getRequestPath($asString = TRUE) {
    
    $path = NULL;
          
    if (isset(self::$Host)) {
      !isset(self::$Host->request_path) ? self::$Host->analyzeRequestPath() : NULL;
      $asString ? $path = implode(self::URI_SLASH, self::$Host->request_path) : $path = self::$Host->request_path;
    }
    else {
      throw new AblePolecat_Host_Exception('Host is not initialized and failed to return request path.');
    }
    return $path;
  }
  
  /**
   * Return the query string part of request URI.
   *
   * @param bool $asString If TRUE return query string as URL encoded string, otherwise return parts in array.
   * 
   * @return mixed return type depends on value of $asString
   */
  public static function getRequestQueryString($asString = TRUE) {
    
    $query_string = NULL;
    
    if (isset(self::$Host)) {
      $query_string = self::$Host->query_string;
      if ($asString) {
        $query_string = array();
        foreach(self::$Host->query_string as $parameter => $args) {
          $query_string[] = sprintf("%s=%s", $parameter, rawurlencode(implode('+', $args)));
        }
        $query_string = implode('&', $query_string);          
      }
    }
    else {
      throw new AblePolecat_Host_Exception('Host is not initialized and failed to return query string.');
    }
    return $query_string;
  }
  
  /**
   * @return string Base URL (host name[/alias]).
   */
  public function getBaseUrl($trailing_slash = TRUE) {
    
    $host_url = NULL;
    
    if (isset(self::$Host)) {
      $trailing_slash ? $host_url = self::$Host->host_url : $host_url = rtrim(self::$Host->host_url, self::URI_SLASH);
    }
    else {
      throw new AblePolecat_Host_Exception('Host is not initialized and failed to return base URL.');
    }
    return $host_url;
  }
    
  /********************************************************************************
   * Create/destroy methods
   ********************************************************************************/
  
  /**
   * Filter (sanitize) entity body if sent (e.g. POST data).
   */
  protected function filterEntityBody() {
    
    //
    // Allocate array in any case - container for invalid path parts
    // @see getRequestPathInfo()
    //
    $this->entity_body = array();
    
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
  }
  
  /**
   * Filter (sanitize) query string if sent.
   */
  protected function filterQueryString() {
    
    $this->query_string = array();

    foreach($_REQUEST as $argName => $argValue) {
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
  
  /**
   * Initialize URL of host (or virtual host).
   */
  protected function initializeHostUrl() {
    //
    // @todo: HTTPS
    //
    $protocol = 'http';
    
    //
    // @todo: this order works on Win 7Pro and CENTOS - not tested otherwise
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
    //
    if(isset($_SERVER['SCRIPT_NAME'])) {
      $this->alias = str_replace(array('index.php', 'search.php', 'util.php'), '', $_SERVER['SCRIPT_NAME']);
    }
    if (isset($this->alias) && ($this->alias == self::URI_SLASH)) {
      //
      // This can cause problems with string manipulation. Unset alias if it is only '/'
      //
      $this->alias = NULL;
    }
    
    //
    // base URL protocol://host[/alias]
    // 
    $this->host_url = "$protocol://$host";
    
    //
    // Add any alias
    //
    if (isset($this->alias)) {
      $this->host_url .=  $this->alias;
    }
    else {
      $this->host_url .= self::URI_SLASH;
    }
  }
  
  /**
   * Break off path from host name and analyze components.
   */
  protected function analyzeRequestPath() {
    
    $this->request_path_info = array();
    
    isset($_SERVER['REQUEST_METHOD']) ? $method = $_SERVER['REQUEST_METHOD'] : $method = 'GET';
    
    switch ($method) {
      default:        
        //
        // Remove trailing slash, alias and query string, if any, from request URI
        //
        isset($this->alias) ? $alias = $this->alias : $alias = '';
        isset($_SERVER['REQUEST_URI']) ? $request_uri = $_SERVER['REQUEST_URI'] : $request_uri = '';
        isset($_SERVER['QUERY_STRING']) ? $query_string = $_SERVER['QUERY_STRING'] : $query_string = '';
        $this->request_path_info[self::URI_PATH] = trim(str_replace($alias, '', str_replace($query_string, '', $request_uri)), self::URI_SLASH.'?');
        $this->request_path = explode(self::URI_SLASH, $this->request_path_info[self::URI_PATH]);
        
        //
        // Is there anything left of the path?
        //
        if (isset($this->request_path[0]) && ($this->request_path[0] != '')) {
          //
          // Is it a request for a recognized resource on the system?
          //
          $resourceName = $this->validateResourceName($this->request_path[0]);
          if (strcasecmp($this->request_path[0], $resourceName) == 0) {
            //
            // Yes, a valid resource name was given.
            //
            $this->request_path_info[self::URI_RESOURCE_NAME] = $resourceName;
            $this->request_path_info[self::URI_REDIRECT] = FALSE;
          }
          else {
            //
            // No, an invalid resource name was given; redirect to search.
            //
            $this->request_path_info[self::URI_RESOURCE_NAME] = self::RESOURCE_NAME_SEARCH;
            $this->request_path_info[self::URI_REDIRECT] = TRUE;
          }
        }
        else {
          //
          // Otherwise, this is a request for the home page.
          //
          $this->request_path_info[self::URI_RESOURCE_NAME] = self::RESOURCE_NAME_HOME;
          $this->request_path_info[self::URI_REDIRECT] = FALSE;
        }
        break;
      case 'POST':
        //
        // Build a query string from the POST data
        //
        $query_string = $this->makeRequestQueryString($this->entity_body);
        $redirect_uri = sprintf("%s%s%s?%s",
          $this->host_url,
          self::RESOURCE_NAME_SEARCH,
          self::URI_SLASH,
          $query_string
        );
        header("Location: $redirect_uri");
        exit(0);
    }
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
        $query_string_parts[] = sprintf("%s=%s", $parameter, rawurlencode(implode('+', $args)));
      }
      $query_string = implode('&', $query_string_parts);
    }
    return $query_string;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
  }
  
  final protected function __construct() {
    
    //
    // Turn on output buffering.
    //
    ob_start();
    
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
    // Sub-classes can extend constructor via initialize().
    //
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    //
    // Flush output buffer.
    //
    ob_end_flush();
  }
}