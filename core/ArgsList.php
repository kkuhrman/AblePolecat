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

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'DynamicObject.php');

/**
 * Interface for argument lists.
 */
interface AblePolecat_ArgsListInterface extends AblePolecat_DynamicObjectInterface {
}

/**
 * Standard argument list.
 */
class AblePolecat_ArgsList extends AblePolecat_DynamicObjectAbstract implements AblePolecat_ArgsListInterface {
  
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