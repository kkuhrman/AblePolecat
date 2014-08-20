<?php
/**
 * @file      polecat/core/Resource.php
 * @brief     The 'Model' part of the MVC triad aka a resource on the web.
 *
 *
 * According to Richardson/Ruby (@see ISBN 978-0-596-52926-0), a Resource Oriented
 * Architecture involves four concepts:
 * 1. Resource (similar to data "Model", as in "MVC", in fossil talk).
 * 2. URI (where it is located on the web, the address @see http://www.w3.org/Addressing/)
 * 3. Representation (similar to "View", as in "MVC", in fossil talk).
 * 4. Links between Resources (e.g. previous/next links on paginated result set).
 * And also four properties:
 * 1. Addressability
 * 2. Statelessness
 * 3. Connectedness
 * 4. A uniform interface
 *
 * @todo: and the REST (pun intended)
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */ 

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar', 'String.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Resource.php')));

interface AblePolecat_ResourceInterface extends AblePolecat_CacheObjectInterface, AblePolecat_AccessControl_ResourceInterface {
  
  /**
   * Returns resource URI.
   *
   * @return string URI or NULL.
   */
  public function getUri();
  
  /**
   * PHP magic method.
   */
  public function __set($name, $value);
  
  /**
   * PHP magic method.
   */
  public function __get($name);
  
  /**
   * PHP magic method.
   */
  public function __isset($name);
  
  /**
   * PHP magic method.
   */
  public function __unset($name);
}

abstract class AblePolecat_ResourceAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_ResourceInterface {
  
  /**
   *  @var public class data members.
   */
  private $properties;
  
  /**
   * @var string URI.
   */
  private $uri;
  
  /**
   * Validates request URI path to ensure resource request can be fulfilled.
   *
   * @throw AblePolecat_Resource_Exception If request URI path is not validated.
   */
  abstract protected function validateRequestPath();
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
   
  /********************************************************************************
   * Implementation of AblePolecat_ResourceInterface
   ********************************************************************************/
    
  /**
   * Returns resource URI.
   *
   * @return string URI or NULL.
   */
  public function getUri() {
    return $this->uri;
  }
  
  /**
   * PHP magic method.
   */
  public function __set($name, $value) {
    
    $this->checkAlloc();
    if (!is_a($value, 'AblePolecat_DataInterface')) {
      $value = AblePolecat_Data_Scalar_String::typeCast($value);
    }
    $this->properties[$name] = $value;
  }
  
  /**
   * PHP magic method.
   */
  public function __get($name) {
    $this->checkAlloc();
    if (isset($this->properties[$name])) {
      return $this->properties[$name];
    }

    $trace = debug_backtrace();
    trigger_error(
      'Undefined property via __get(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_NOTICE
    );
    return null;
  }
  
  /**
   * PHP magic method.
   */
  public function __isset($name) {
    $this->checkAlloc();
    return isset($this->properties[$name]);
  }
  
  /**
   * PHP magic method.
   */
  public function __unset($name) {
    $this->checkAlloc();
    unset($this->properties[$name]);
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Allocates array if not already allocated.
   *
   * It seems intuitively inefficient to step into this function every time.
   * It is a protection against failure to call parent method in subclasses.
   * (for example, subclass overrides initialize()).
   */
  private function checkAlloc() {
    if (!isset($this->properties)) {
      $this->properties = array();
    }
  }
  
  /**
   * Returns assigned or default value and will not trigger an error.
   * 
   * @param string $name Name of property.
   * @param mixed $default Default value to return if not assigned.
   *
   * @return mixed Assigned value of property given by $name if assigned, otherwise $default.
   */
  public function getPropertySafe($name, $default = NULL) {
    
    $property = $default;
    $this->checkAlloc();
    
    if (isset($this->properties[$name])) {
      $property = $this->properties[$name];
    }
    return $property;
  }
  
  public function getFirstProperty() {
    $this->checkAlloc();
    $property = reset($this->properties);
    return $property;
  }
  
  public function getNextProperty() {
    $this->checkAlloc();
    $property = next($this->properties);
    return $property;
  }
  
  public function getPropertyKey() {
    $this->checkAlloc();
    $property = key($this->properties);
    return $property;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    //
    // throw exception if request URI path is not valid for resource
    //
    $this->validateRequestPath();
    $this->uri = AblePolecat_Server::getRequest()->getBaseUrl() . AblePolecat_Server::getRequest()->getRequestPath(TRUE);
  }
}