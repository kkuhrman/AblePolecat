<?php
/**
 * @file: Node.php
 * Any part of message HEAD or BODY.
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'DynamicObject.php');

interface AblePolecat_Message_NodeInterface extends AblePolecat_DynamicObjectInterface {
  
  /**
   * Creational method.
   */
  // public static function create();
  
  public function getFirstChild();
  
  public function getNextChild();
  
  public function getChildKey();
  
  // public function getFirstProperty();
  
  // public function getNextProperty();
  
  /**
   * These PHP magic methods must be implemented.
   */
  // public function __set($name, $value);

  // public function __get($name);
  
  // public function __isset($name);

  // public function __unset($name);
}

abstract class AblePolecat_Message_NodeAbstract extends AblePolecat_DynamicObjectAbstract implements AblePolecat_Message_NodeInterface {
  
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
  protected function initialize() {
    $this->children = array();
    $this->parent = NULL;
  }
  
  /**
   * Override parent class to handle parent/child nodes.
   */
  public function __set($name, $value) {
    if (is_a($value, 'AblePolecat_Message_NodeInterface')) {
      $child = $value;
      $child->parent = $this;
      $this->children[$name] = $child;
    }
    else {
      parent::__set($name, $value);
    }
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
}
