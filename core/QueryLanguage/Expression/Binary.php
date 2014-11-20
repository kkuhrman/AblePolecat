<?php
/**
 * @file      polecat/core/QueryLanguage/Expression/Binary.php
 * @brief     Interface for a binary query language expression.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Expression.php')));

interface AblePolecat_QueryLanguage_Expression_BinaryInterface extends AblePolecat_QueryLanguage_ExpressionInterface {
  
  /**
   * @return string 'Left' value of binary expression.
   */
  public function lvalue();
  
  /**
   * @return string Binary expression operator.
   */
  public function operator();
  
  /**
   * @return string 'Right' value of binary expression.
   */
  public function rvalue();
}

abstract class AblePolecat_QueryLanguage_Expression_BinaryAbstract implements AblePolecat_QueryLanguage_Expression_BinaryInterface {
  
  const LVALUE    = 'lvalue';
  const OPERATOR  = 'operator';
  const RVALUE    = 'rvalue';
  
  /**
   * @var raw expression data.
   */
  private $expression;
  
  /********************************************************************************
   * Implementation of AblePolecat_QueryLanguage_Expression_BinaryInterface.
   ********************************************************************************/
  
  /**
   * @return string 'Left' value of binary expression.
   */
  public function lvalue() {
    return $this->expression[self::LVALUE];
  }
  
  /**
   * @return string Binary expression operator.
   */
  public function operator() {
    return $this->expression[self::OPERATOR];
  }
  
  /**
   * @return string 'Right' value of binary expression.
   */
  public function rvalue() {
    return $this->expression[self::RVALUE];
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Populate data members.
   *
   * @param Array $args Raw or modified constructor args.
   * @see func_get_args().
   */
  protected function populate($args) {
    if (isset($args) && is_array($args)) {
      try {
        foreach($args as $key => $value) {
          $strvalue = AblePolecat_Data_Primitive_Scalar_String::typeCast($value);
          switch($key) {
            default:
              AblePolecat_Command_Log::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), sprintf("Excess data passed to %s constructor. %s.", get_class($this), $strvalue), AblePolecat_LogInterface::WARNING);
              break;
            case 0:
              $this->expression[self::LVALUE] = $strvalue;
              break;
            case 1:
              $this->expression[self::OPERATOR] = $strvalue;
              break;
            case 2:
              $this->expression[self::RVALUE] = $strvalue;
              break;
          }
        }
      }
      catch (AblePolecat_Data_Exception $Exception) {
        throw new AblePolecat_QueryLanguage_Exception(
          sprintf("Failed to initialize %s. Operands and operator must be scalar or implement __toString(). %s passed.", 
            get_class($this), 
            gettype($value)
          ), 
          AblePolecat_Error::INVALID_TYPE_CAST
        );
      }
    }
  }
  
  final public function __construct() {
    //
    // transformation support class
    // @todo: verify all core classes loadable by convention.
    //
    // $ClassRegistry = NULL;
    // $CommandResult = AblePolecat_Command_GetRegistry::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), 'AblePolecat_Registry_Class');
    // if ($CommandResult->success()) {
      // $ClassRegistry = $CommandResult->value();
    // }
    // if (isset($ClassRegistry) && !$ClassRegistry->isLoadable('AblePolecat_Data_Primitive_Scalar_String')) {
      // $ClassRegistry->registerLoadableClass(
        // 'AblePolecat_Data_Primitive_Scalar_String', 
        // implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar', 'String.php')),
        // 'typeCast'
      // );
    // }
      
    $this->expression = array(
      self::LVALUE => '',
      self::OPERATOR => '',
      self::RVALUE => '',
    );
    $this->populate(func_get_args());
  }
}