<?php
/**
 * @file      polecat/core/AccessControl/Resource/Locater.php
 * @brief     Simple URL interface.
 *
 * Intended to follow interface specified by W3C but does not provide public access to 
 * properties (get/set methods provided).
 *
 * @see http://www.w3.org/TR/url/#url
 *
 * @todo there is much work to do to comply with W3C spec. At present would not trust this 
 * class as more than a crude container for local file paths names and very simple web addresses.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

if (!defined('URI_SLASH')) {
  $URI_SLASH = chr(0x2F);
  define('URI_SLASH', $URI_SLASH);
}

interface AblePolecat_AccessControl_Resource_LocaterInterface {
  
  const URI_SLASH = URI_SLASH;
  
  /**
   * Create URL.
   * 
   * @param DOMString $url Relative or absolute path.
   * @param optional DOMString $baseURL.
   *
   * @return object Instance of class implementing AblePolecat_AccessControl_Resource_LocaterInterface or NULL.
   */
  public static function create($url, $baseURL = NULL);
  
  /**
   * @return DOMString protocol.
   */
  public function getProtocol();
  
  /**
   * @return DOMString username.
   */
  public function getUsername();
  
  /**
   * @return DOMString password.
   */
  public function getPassword();
  
  /**
   * @return DOMString host.
   */
  public function getHost();
  
  /**
   * @return DOMString hostname.
   */
  public function getHostname();
  
  /**
   * @return DOMString port.
   */
  public function getPort();
  
  /**
   * @return DOMString pathname.
   */
  public function getPathname();
  
  /**
   * @return DOMString search.
   */
  public function getSearch();
  
  /**
   * @return DOMString hash.
   */
  public function getHash();
  
  /**
   * @return DOMString filename.
   */
  public function getFilename();
  
  /**
   * @return DOMString origin.
   */
  public function getOrigin();
  
  /**
   * Set protocol.
   *
   * @param DOMString $protocol
   */
  public function setProtocol($protocol);
  
  /**
   * Set username.
   *
   * @param DOMString $username
   */
  public function setUsername($username);
  
  /**
   * Set password.
   *
   * @param DOMString $password
   */
  public function setPassword($password);
  
  /**
   * Set host.
   *
   * @param DOMString $host
   */
  public function setHost($host);
  
  /**
   * Set hostname.
   *
   * @param DOMString $hostname
   */
  public function setHostname($hostname);
  
  /**
   * Set port.
   *
   * @param DOMString $port
   */
  public function setPort($port);
  
  /**
   * Set pathname.
   *
   * @param DOMString $pathname
   */
  public function setPathname($pathname);
  
  /**
   * Set search.
   *
   * @param DOMString $search
   */
  public function setSearch($search);
  
  /**
   * Set hash.
   *
   * @param DOMString $hash
   */
  public function setHash($hash);
  
  /**
   * Set filename.
   *
   * @param DOMString $filename
   */
  public function setFilename($filename);
  
  /**
   * Return all unique names of parameters in list.
   *
   * @return Array Names of parameters.
   */
  public function getParameterNames();
  
  /**
   * Return all values for parameter by given name.
   * 
   * @param DOMString $name Name of given parameter.
   *
   * @return Array All parameter values or NULL.
   */
  public function getParameterValues($name);
  
  /**
   * @return bool TRUE if given parameter set, otherwise FALSE.
   */
  public function hasParameter($name);
  
  /**
   * Get value of given parameter.
   *
   * @param DOMString $name Name of parameter to update.
   *
   * @return DOMString Value of parameter or NULL.
   */
   public function getParameter($name);
   
  /**
   * Set value of given parameter.
   *
   * @param DOMString $name Name of parameter to update.
   * @param DOMString $value Parameter value.
   */
  public function setParameter($name, $value);
  
  /**
   * Add given parameter to list.
   *
   * @param DOMString $name Name of parameter to add to list.
   * @param DOMString $value Parameter value.
   */
  public function addParameter($name, $value);
  
  /**
   * Remove given parameter from list.
   *
   * @param DOMString $name Name of parameter to remove.
   */
  public function removeParameter($name);
  
  /**
   * Clear all parameters, reset list.
   */
  public function clearParameters();
  
  /**
   * Return URL as a string.
   *
   * @return DOMString href.
   */
  public function __toString();
}

class AblePolecat_AccessControl_Resource_Locater implements AblePolecat_AccessControl_Resource_LocaterInterface {
  
  /**
   * @var string raw URL as passed to constructor.
   * @see create().
   */
  private $raw_url;
  
  /**
   * @var DOMString protocol.
   */
  protected $m_protocol;
  
  /**
   * @var DOMString username.
   */
  protected $m_username;
  
  /**
   * @var DOMString password.
   */
  protected $m_password;
  
  /**
   * @var DOMString host.
   */
  protected $m_host;
  
  /**
   * @var DOMString hostname.
   */
  protected $m_hostname;
  
  /**
   * @var DOMString port.
   */
  protected $m_port;
  
  /**
   * @var DOMString pathname.
   */
  protected $m_pathname;
  
  /**
   * @var DOMString search.
   */
  protected $m_search;
  
  /**
   * @var DOMString hash.
   */
  protected $m_hash;
  
  /**
   * @var DOMString filename.
   */
  protected $m_filename;
  
  /**
   * @var DOMString origin.
   */
  protected $m_origin;
  
  /**
   * @var Array parameters.
   */
  protected $m_parameters;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Resource_LocaterInterface.
   ********************************************************************************/
  
  /**
   * Create URL.
   * 
   * @param DOMString $url Relative or absolute path.
   * @param optional DOMString $baseURL.
   *
   * @return object Instance of class implementing AblePolecat_AccessControl_Resource_LocaterInterface or NULL.
   */
  public static function create($url, $baseURL = NULL) {
    isset($baseURL) ? $url = $baseURL . self::URI_SLASH . $url : NULL;
    $Locater = new AblePolecat_AccessControl_Resource_Locater($url);
    return $Locater;
  }
  
  /**
   * @return DOMString protocol.
   */
  public function getProtocol() {
    return $this->m_protocol;
  }
  
  /**
   * @return DOMString username.
   */
  public function getUsername() {
    return $this->m_username;
  }
  
  /**
   * @return DOMString password.
   */
  public function getPassword() {
    return $this->m_password;
  }
  
  /**
   * @return DOMString host.
   */
  public function getHost() {
    return $this->m_host;
  }
  
  /**
   * @return DOMString hostname.
   */
  public function getHostname() {
    return $this->m_hostname;
  }
  
  /**
   * @return DOMString port.
   */
  public function getPort() {
    return $this->m_port;
  }
  
  /**
   * @return DOMString pathname.
   */
  public function getPathname() {
    return $this->m_pathname;
  }
  
  /**
   * @return DOMString search.
   */
  public function getSearch() {
    return $this->m_search;
  }
  
  /**
   * @return DOMString hash.
   */
  public function getHash() {
    return $this->m_hash;
  }
  
  /**
   * @return DOMString filename.
   */
  public function getFilename() {
    return $this->m_filename;
  }
  
  /**
   * @return DOMString origin.
   */
  public function getOrigin() {
    return $this->m_origin;
  }
  
  /**
   * Set protocol.
   *
   * @param DOMString $protocol
   */
  public function setProtocol($protocol) {
    $this->m_protocol = $protocol;
  }
  
  /**
   * Set username.
   *
   * @param DOMString $username
   */
  public function setUsername($username) {
    $this->m_username = $username;
  }
  
  /**
   * Set password.
   *
   * @param DOMString $password
   */
  public function setPassword($password) {
    $this->m_password = $password;
  }
  
  /**
   * Set host.
   *
   * @param DOMString $host
   */
  public function setHost($host) {
    $this->m_host = $host;
  }
  
  /**
   * Set hostname.
   *
   * @param DOMString $hostname
   */
  public function setHostname($hostname) {
    $this->m_hostname = $hostname;
  }
  
  /**
   * Set port.
   *
   * @param DOMString $port
   */
  public function setPort($port) {
    $this->m_port = $port;
  }
  
  /**
   * Set pathname.
   *
   * @param DOMString $pathname
   */
  public function setPathname($pathname) {
    $this->m_pathname = $pathname;
  }
  
  /**
   * Set search.
   *
   * @param DOMString $search
   */
  public function setSearch($search) {
    $this->m_search = $search;
  }
  
  /**
   * Set hash.
   *
   * @param DOMString $hash
   */
  public function setHash($hash) {
    $this->m_hash = $hash;
  }
  
  /**
   * Set filename.
   *
   * @param DOMString $filename
   */
  public function setFilename($filename) {
    $this->m_filename = $filename;
  }
  
  /**
   * Return all unique names of parameters in list.
   *
   * @return Array Names of parameters.
   */
  public function getParameterNames() {
    return array_keys($this->m_parameters);
  }
  
  /**
   * Return all values for parameter by given name.
   * 
   * @param DOMString $name Name of given parameter.
   *
   * @return Array All parameter values or NULL.
   */
  public function getParameterValues($name) {
  
    $values = NULL;
    if (isset($this->m_parameters[$name])) {
      $values = $this->m_parameters[$name];
    }
    return $values;
  }
  
  /**
   * @return bool TRUE if given parameter set, otherwise FALSE.
   */
  public function hasParameter($name) {
    return isset($this->m_parameters[$name]);
  }
  
  /**
   * Get value of given parameter.
   *
   * @param DOMString $name Name of parameter to update.
   *
   * @return DOMString Value of parameter or NULL.
   */
   public function getParameter($name) {
    isset($this->m_parameters[$name]) ? $value = $this->m_parameters[$name][0] : $value = NULL;
    return $value;
   }
  
  /**
   * Set value of given parameter.
   *
   * @param DOMString $name Name of parameter to update.
   * @param DOMString $value Parameter value.
   */
  public function setParameter($name, $value) {
    if (isset($this->m_parameters[$name])) {
      unset($this->m_parameters[$name]);
    }
    $this->m_parameters[$name] = array();
    $this->m_parameters[$name][] = $value;
  }
  
  /**
   * Add given parameter to list.
   *
   * @param DOMString $name Name of parameter to add to list.
   * @param DOMString $value Parameter value.
   */
  public function addParameter($name, $value) {
    if (!isset($this->m_parameters[$name])) {
      $this->m_parameters[$name][] = $value;
    }
  }
  
  /**
   * Remove given parameter from list.
   *
   * @param DOMString $name Name of parameter to remove.
   */
  public function removeParameter($name) {
    if (isset($this->m_parameters[$name])) {
      unset($this->m_parameters[$name]);
    }
  }
  
  /**
   * Clear all parameters, reset list.
   */
  public function clearParameters() {
    unset($this->m_parameters);
    $this->m_parameters = array();
  }

  /**
   * Return URL as a string.
   *
   * @return DOMString href.
   */
  public function __toString() {
    $url = '';
    if (isset($this->m_protocol)) {
      $url = $this->m_protocol;
    }
    if ((strlen($this->m_protocol) === 1) && preg_match('/[a-z]/i', $this->m_protocol)) {
      $url .= ':';
    }
    else if (preg_match('/[a-z]/i', $this->m_protocol)) {
      $url .= '://';
    }
    
    isset($this->m_host) ? $url .= $this->m_host : NULL;
    isset($this->m_port) ? $url .= ':' . $this->m_port : NULL;
    // $this->m_username,
    // $this->m_password,
    
    isset($this->m_pathname) ? $url .= $this->m_pathname : NULL;
    
    return $url;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Returns the raw, unencoded URL as passed to constructor.
   *
   * @return string.
   * @see create().
   */
  public function getRawUrl() {
    return $this->raw_url;
  }
  
  /**
   * Extends __construct();
   */
  protected function initialize() {
    $url_parts = parse_url($this->getRawUrl());   
    isset($url_parts['scheme']) ? $this->m_protocol = $url_parts['scheme'] : $this->m_protocol = NULL;
    isset($url_parts['host']) ? $this->m_host = $url_parts['host'] : $this->m_host = NULL;
    isset($url_parts['port']) ? $this->m_port = $url_parts['port'] : $this->m_port = NULL;
    isset($url_parts['user']) ? $this->m_username = $url_parts['user'] : $this->m_username = NULL;
    isset($url_parts['pass']) ? $this->m_password = $url_parts['pass'] : $this->m_password = NULL;
    isset($url_parts['path']) ? $this->m_pathname = $url_parts['path'] : $this->m_pathname = NULL;
    // isset($url_parts['query']);
    // isset($url_parts['fragment']);
    $this->m_hostname = '';
    $this->m_search = '';
    $this->m_hash = '';
    $this->m_filename = '';
    $this->m_origin = '';
    $this->m_parameters = array();
  }
  
  final protected function __construct($url = NULL) {
    $this->raw_url = $url;
    $this->initialize();
  }
}