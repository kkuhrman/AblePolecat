<?php
/**
 * @file: ArgsList.php
 * Encapsulates an argument list passed to a function or class method.
 *
 * Pirates! Args.
 *
 * The argument list object is used in Able Polecat to support function/
 * method overloading in the classic OOP sense by passing a single parameter.
 * Functions/methods can leverage this polymorphic feature by knowing the 
 * named parameters expected and what to do with them or having some other
 * intrinsic behavior to handle them (e.g. building a column list in a SQL 
 * SELECT statement etc).
 *
 * Two basic implementations of this interface are provided. The first, the 
 * 'standard' argument list must be initialized by naming arguments and 
 * assigning values at runtime like so:
 * 
 * $Instance->myProperty = $myValue; 
 *
 * The second, the 'overloaded' argument list allows user to pass a variable
 * argument list to the creational routine but must define ahead of time how
 * to marshal and unmarshal such a list. 
 */

/**
 * Interface for argument lists.
 */
interface AblePolecat_ArgsListInterface {
  
  /**
   * These PHP magic methods must be implemented.
   */
  public function __set($name, $value);

  public function __get($name);
  
  public function __isset($name);

  public function __unset($name);
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create();
}

/**
 * Base class for argument lists.
 */
abstract class AblePolecat_ArgsListAbstract implements AblePolecat_ArgsListInterface {
  
  /**
   *  @var Array Argument list.
   */
  private $arguments;
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  abstract protected function initialize();
  
  public function __set($name, $value) {
    $this->arguments[$name] = $value;
  }

  public function __get($name) {
    if (isset($this->arguments[$name])) {
      return $this->arguments[$name];
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
    return isset($this->arguments[$name]);
  }

  public function __unset($name) {
    unset($this->arguments[$name]);
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
    if (isset($this->arguments[$name])) {
      $property = $this->arguments[$name];
    }
    return $property;
  }
  
  final protected function __construct() {
    $this->arguments = func_get_args();
    $this->initialize();
  }
}

/**
 * Standard argument list.
 */
class AblePolecat_ArgsList extends AblePolecat_ArgsListAbstract {
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
  }
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_ArgsList();
  }
}