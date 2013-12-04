<?php
/**
 * @file: Binary.php
 * Interface for a binary query language expression.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'QueryLanguage', 'Expression.php')));

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
          $strvalue = AblePolecat_Data_Scalar_String::typeCast($value);
          switch($key) {
            default:
              AblePolecat_Server::log(AblePolecat_LogInterface::WARNING,
                sprintf("Excess data passed to %s constructor. %s.", 
                  get_class($this), 
                  $strvalue
                )
              );
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
  
  final public function __construct() {
    if (!AblePolecat_Server::getClassRegistry()->isLoadable('AblePolecat_Data_Scalar_String')) {
      AblePolecat_Server::getClassRegistry()->registerLoadableClass(
        'AblePolecat_Data_Scalar_String', 
        implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Data', 'Scalar', 'String.php')),
        'typeCast'
      );
    }
      
    $this->expression = array(
      self::LVALUE => '',
      self::OPERATOR => '',
      self::RVALUE => '',
    );
    $this->populate(func_get_args());
  }
}