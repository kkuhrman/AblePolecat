<?php
/**
 * @file: Node.php
 * Any part of message HEAD or BODY.
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

interface AblePolecat_Message_NodeInterface {
  
  /**
   * Creational method.
   */
  public static function create();
  
  public function getFirstChild();
  
  public function getNextChild();
  
  public function getFirstProperty();
  
  public function getNextProperty();
  
  /**
   * These PHP magic methods must be implemented.
   */
  public function __set($name, $value);

  public function __get($name);
  
  public function __isset($name);

  public function __unset($name);
}

abstract class AblePolecat_Message_NodeAbstract implements AblePolecat_Message_NodeInterface {
  
  /**
   *  @var Similar to attributes in XML.
   */
  private $properties;
  
  /**
   * @var Similar to child elements in XML.
   */
  private $children;
  
  /**
   * @var Parent element.
   */
  protected $parent;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize properties.
   */
  abstract protected function initialize();


  public function __set($name, $value) {
    if (is_a($value, 'AblePolecat_Message_NodeInterface')) {
      $child = $value;
      $child->parent = $this;
      $this->children[$name] = $child;
    }
    else {
      // is_object($value) ? $type = get_class($value) : $type = gettype($value);
      // trigger_error("Able Polecat message properties must implement AblePolecat_Message_NodeInterface. $type given", 
        // E_USER_ERROR);
      $this->properties[$name] = $value;
    }
  }

  public function __get($name) {
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

  public function __isset($name) {
    return isset($this->properties[$name]);
  }

  public function __unset($name) {
    unset($this->properties[$name]);
  }
  
  public function getFirstChild() {
    
    $child = reset($this->children);
    return $child;
  }
  
  public function getNextChild() {
    
    $child = next($this->children);
    return $child;
  }
  
  public function getChildKey() {
    
    $child = key($this->children);
    return $child;
  }
  
  public function getFirstProperty() {
    
    $property = reset($this->properties);
    return $property;
  }
  
  public function getNextProperty() {
    
    $property = next($this->properties);
    return $property;
  }
  
  public function getPropertyKey() {
    
    $property = key($this->properties);
    return $property;
  }
  
  final protected function __construct() {
    $this->properties = array();
    $this->children = array();
    $this->parent = NULL;
    $this->initialize();
  }
}

/**
  * Exceptions thrown by Able Polecat message sub-classes.
  */
class AblePolecat_Message_Exception extends AblePolecat_Exception {
}
