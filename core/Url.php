<?php
/**
 * @file: Url.php
 * Encapsulates system (aka 'base') URL and URL related functions.
 * @todo: should implement AblePolecat_AccessControl_Resource_LocaterInterface
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Server', 'Paths.php')));

if (!defined('URI_SLASH')) {
    $URI_SLASH = chr(0x2F);
  // $URI_SLASH = '%2F';
  // $URI_SLASH = '/';
  define('URI_SLASH', $URI_SLASH);
}
  
class AblePolecat_Url {
  
  const URI_SLASH           = URI_SLASH;
  const URI_PATH            = 'path';
  const URI_RESOURCE_NAME   = 'resource_name'; // Name of object type (e.g. product, location, etc).
  const URI_RESOURCE_ID     = 'resource_id'; // Specific id of resource (e.g. SKU etc).
  const URI_PATH_EXTRA      = 'uri_extra'; // Path names, which exceed Able Tabby URI syntax requirements.
  const URI_REDIRECT        = 'uri_redirect'; // TRUE if resolved path is not the original request, otherwise FALSE.
  const URI_SEARCH_PARAM    = 'q'; // Parameter in query string identifying string to send to search feature.
  
  /**
   * @var string protocol://host_name/[alias].
   */
  private $base_url;
    
  /**
   * @var string Part of request URI after host/alias and before query string.
   */
  private $request_path;
  
  /**
   * @var Array Analysis of request URI.
   */
  private $request_path_info;
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  protected function initialize() {
    $this->base_url = NULL;
    $this->request_path = NULL;
    $this->request_path_info = NULL;
  }
  
  /**
   * @return string Base URL.
   */
  public function getBaseUrl($trailing_slash = TRUE) {
    
    if (!isset($this->base_url)) {
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
      // Apache alias?
      //
      $alias = NULL;
      if(isset($_SERVER['SCRIPT_NAME'])) {
        $alias = str_replace(array('index.php', 'search.php', 'util.php'), '', $_SERVER['SCRIPT_NAME']);
      }
      
      //
      // base URL protocol://host/path
      // 
      $this->base_url = "$protocol://$host";
      
      //
      // Add any alias
      //
      if (isset($alias)) {
        $this->base_url .=  $alias;
      }
      else {
        $this->base_url .= self::URI_SLASH;
      }
    }
    $trailing_slash ? $base_url = $this->base_url : $base_url = rtrim($this->base_url, self::URI_SLASH);
    return $base_url;
  }
  
  /**
   * @return string Part of request URI after host/alias and before query string.
   */
  public function getRequestPath() {
    
    $path = '';
    
    if (!isset($this->request_path)) {
      if (isset($_SERVER['REDIRECT_URL'])) {
        //
        // Redirected to index.php (URL rewrite rule).
        //
        $alias = NULL;
        if(isset($_SERVER['SCRIPT_NAME'])) {
          $pathinfo = pathinfo($_SERVER['SCRIPT_NAME']);
          // $alias = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
          $alias = str_replace(array('index.php', 'search.php', 'util.php'), '', $_SERVER['SCRIPT_NAME']);
        }
        if(isset($alias) && ($alias != self::URI_SLASH)) {
          $this->request_path = explode(self::URI_SLASH, trim(str_replace($alias, '', $_SERVER['REDIRECT_URL']), self::URI_SLASH));
        }
        else {
          $this->request_path = explode(self::URI_SLASH, trim($_SERVER['REDIRECT_URL'], self::URI_SLASH));
        }
      }
      else {
        //
        // Direct request for index.php (home) or search.php
        //
        $alias = NULL;
        if(isset($_SERVER['SCRIPT_NAME'])) {
          $pathinfo = pathinfo($_SERVER['SCRIPT_NAME']);
          $basename = $pathinfo['basename'];
          if ($basename == 'index.php') {
            //
            // GET request for home page
            //
            $this->request_path = array(self::URI_SLASH);
          }
          else {
            //
            // GET or POST request for search feature
            //
            $alias = str_replace($basename, '', $_SERVER['SCRIPT_NAME']);
            if(isset($alias)) {
              if (isset($_SERVER['QUERY_STRING'])) {
                $this->request_path = explode(self::URI_SLASH, trim(str_replace($alias, '', str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI'])), self::URI_SLASH));
              }
              else {
                $this->request_path = explode(self::URI_SLASH, trim(str_replace($alias, '', $_SERVER['REQUEST_URI']), self::URI_SLASH));
              }
            }
            else {
              $this->request_path = explode(self::URI_SLASH, trim($_SERVER['REQUEST_URI'], self::URI_SLASH));
            }
          }
        }
      }
    }
    $path = implode(self::URI_SLASH, $this->request_path);
    return $path;
  }
  
  /**
   * Similar to PHP pathinfo() except returns info about URI request path.
   * 
   * @return Array.
   */
  public function getRequestPathInfo() {
    
    if (!isset($this->request_path_info)) {
      //
      // Get 'corrected' request path.
      //
      $path = $this->getRequestPath();
      $this->request_path_info = array(
        self::URI_PATH => $path,
        self::URI_RESOURCE_NAME => self::URI_SLASH,
        self::URI_PATH_EXTRA => array(),
        self::URI_REDIRECT => FALSE,
      );
      
      //
      // Save search string if any.
      //
      isset($_REQUEST[self::URI_SEARCH_PARAM]) ? $searchString = $_REQUEST[self::URI_SEARCH_PARAM] : $searchString = NULL;
      isset($searchString) ? $this->request_path_info[self::URI_SEARCH_PARAM] = $searchString : NULL;
      
      //
      // How many levels deep is the path?
      //
      $path_depth = count($this->request_path);
      switch($path_depth) {
        case 1:
          switch($this->request_path[0]) {
            case self::URI_SLASH:
              $this->request_path_info[self::URI_RESOURCE_NAME] = self::URI_SLASH;
              break;
            default:
              $resourceName = $this->validateResourceName($this->request_path[0]);
              if (strcasecmp($this->request_path[0], $resourceName) == 0) {
                //
                // A valid resource name was given.
                //
                $this->request_path_info[self::URI_RESOURCE_NAME] = $resourceName;
              }
              else {
                //
                // An invalid resource name was given, redirect to search.
                //
                $this->request_path_info[self::URI_RESOURCE_NAME] = self::URI_SLASH;
                $this->request_path_info[self::URI_PATH_EXTRA][] = $this->request_path[0];
                $this->request_path_info[self::URI_REDIRECT] = TRUE;
              }
              break;
          }
          break;
        case 2:
          switch($this->request_path[0]) {
            case self::URI_SLASH:
              $this->request_path_info[self::URI_RESOURCE_NAME] = self::URI_SLASH;
              $this->request_path_info[self::URI_PATH_EXTRA][] = $this->request_path[1];
              $this->request_path_info[self::URI_REDIRECT] = TRUE;
              break;
            default:
              $resourceName = $this->validateResourceName($this->request_path[0]);
              if (strcasecmp($this->request_path[0], $resourceName) == 0) {
                //
                // A valid resource name was given.
                //
                $this->request_path_info[self::URI_RESOURCE_NAME] = $resourceName;
                $this->request_path_info[self::URI_RESOURCE_ID] = $this->request_path[1];
              }
              else {
                //
                // An invalid resource name was given, redirect to search.
                //
                $this->request_path_info[self::URI_RESOURCE_NAME] = self::URI_SLASH;
                $this->request_path_info[self::URI_PATH_EXTRA][] = $this->request_path[0];
                $this->request_path_info[self::URI_PATH_EXTRA][] = $this->request_path[1];
                $this->request_path_info[self::URI_REDIRECT] = TRUE;
              }
              break;
          }
          break;
        default:
          //
          // Exceeds standard syntax length.
          //
          $this->request_path_info[self::URI_RESOURCE_NAME] = self::URI_SLASH;
          $this->request_path_info[self::URI_REDIRECT] = TRUE;
          foreach($this->request_path as $key => $tag) {
            $this->request_path_info[self::URI_PATH_EXTRA][] = $tag;
          }
          break;
      }
    }
    return $this->request_path_info;
  }
  
  /**
   * Validates resource syntax rules.
   *
   * @param string $requestedResourceName Name of requested resource.
   *
   * @return string Name of valid resource (default is 'search').
   */
  public function validateResourceName($requestedResourceName) {
    
    $resolvedResourceName = strtolower($requestedResourceName);
    return $resolvedResourceName;
  }
  
  /**
   * @param mixed $url Unencoded URL.
   * 
   * @return AblePolecat_Url or NULL;
   */
  public static function create($url = NULL) {
    
    $Url = new AblePolecat_Url();
    if (isset($url)) {
      //
      // @todo: handle creation of a URL :/
      //
    }
    return $Url;
  }
  
  final protected function __construct() {
    $this->initialize();
  }
}

/**
 * Exceptions thrown by Able Polecat URL functions.
 */
class AblePolecat_Url_Exception extends AblePolecat_Exception {
}