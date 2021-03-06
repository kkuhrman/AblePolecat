<?php
/**
 * @file      polecat/core/Command.php
 * @brief     Encapsulates synchronous interaction between two objects within scope of single script execution.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Static.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Result.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Command.php')));

interface AblePolecat_CommandInterface extends AblePolecat_AccessControl_Article_StaticInterface {
  
  /**
   * Indicates which direction to pass command along CoR.
   *
   * @return string both | forward | reverse.
   */
  public static function direction();
  
  /**
   * Return reference to object which invoked command.
   *
   * @return AblePolecat_AccessControl_SubjectInterface Object which invoked command.
   */
  public function getInvoker();
    
  /**
   * Return command arguments.
   *
   * @return AblePolecat_ArgsListInterface.
   */
  public function getArguments();
  
  /**
   * Invoke the command and return response from target.
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker Agent or role invoking command.
   * @param mixed $Arguments Command arguments.
   *
   * @return AblePolecat_Command_Result.
   */
  public static function invoke(
    AblePolecat_AccessControl_SubjectInterface $Invoker, 
    $Arguments = NULL
  );
}

abstract class AblePolecat_CommandAbstract implements AblePolecat_CommandInterface {
  
  /**
   * @var AblePolecat_AccessControl_SubjectInterface
   */
  private $Invoker;
  
  /**
   * @var AblePolecat_ArgsListInterface
   */
  private $Arguments;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CommandInterface.
   ********************************************************************************/
  
  /**
   * Return reference to object which invoked command.
   *
   * @return AblePolecat_AccessControl_SubjectInterface Object which invoked command.
   */
  public function getInvoker() {
    return $this->Invoker;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Commands accept variable args list - this helper will throw exception on type violation.
   * 
   * @param string $name Name of command.
   * @param mixed $VarArgs Variable arguments list.
   * @param int $ArgNumber Ordinal in the argument list.
   * @parameter mixed $expectedType Expected type.
   * @paramter string $className Provided if expected type is object.
   *
   * @return Value of argument if exception is not thrown.
   * @throw AblePolecat_Command_Exception on type violation.
   */
  protected static function checkArgument($name, $VarArgs, $ArgNumber, $expectedType, $expectedClassName = NULL) {
    
    $ArgValue = NULL;
    $givenType = 'NULL';
    $exceptionMsg = "$name expects argument";
    
    if (isset($VarArgs) && isset($ArgNumber) && isset($expectedType)) {
      //
      // Get argument value and type
      //
      isset($VarArgs[$ArgNumber]) ? $ArgValue = $VarArgs[$ArgNumber] : NULL;
      $givenType = gettype($ArgValue);
      $exceptionMsg .= ' ' . strval($ArgNumber);
      
      if ((0 == strcasecmp($expectedType, 'object')) && is_object($ArgValue)) {
        //
        // Expecting an object, check class name
        //
        if (isset($expectedClassName)) {
          $exceptionMsg .= ' to be type ' . $expectedClassName . '.';
          if (is_a($ArgValue, $expectedClassName)) {
            $exceptionMsg = NULL;
          }
          else {
            $exceptionMsg .= get_class($ArgValue) . ' given.';
          }
        }
        else {
          //
          // expecting undefined object
          //
          $exceptionMsg = NULL;
        }
      }
      else {
        //
        // Expecting scalar variable
        //
        if (0 == strcasecmp($givenType, $expectedType)) {
          $exceptionMsg = NULL;
        }
        else {
          $exceptionMsg .= " to be type $expectedType. $givenType given.";
        }
      }
    }
    if (isset($exceptionMsg)) {
      throw new AblePolecat_Command_Exception($exceptionMsg);
    }
    
    return $ArgValue;
  }
  
  /**
   * Dispatch command to a suitable target.
   *
   * @return AblePolecat_Command_Result.
   */
  protected function dispatch() {
    $Result = AblePolecat_Command_Chain::dispatch($this);
    if (!isset($Result) || !is_a($Result, 'AblePolecat_Command_Result')) {
      $msg = AblePolecat_Data::getDataTypeName($this) . '::invoke() failed to return a valid result object. Check log for details.';
      throw new AblePolecat_Command_Exception($msg);
    }
    return $Result;
  }
  
  /**
   * Return command arguments.
   *
   * @return AblePolecat_ArgsListInterface.
   */
  public function getArguments() {
    return $this->Arguments;
  }
  
  /**
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker
   * @param mixed $Arguments
   * @see invoke().
   */
  final protected function __construct(AblePolecat_AccessControl_SubjectInterface $Invoker, $Arguments = NULL) {
    $this->Invoker = $Invoker;    
    $this->Arguments = $Arguments;
  }
}