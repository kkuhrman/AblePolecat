<?php
/**
 * @file      polecat/core/QueryLanguage/Statement.php
 * @brief     Basic data/object retrieval language statement interface.
 *
 * The (abstract) base class implementation of this interface does not support implicit inner
 * joins in SELECT statements (tables lists). Sub-classes must implement support for table
 * lists and DML operations other than SELECT.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar', 'String.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'QueryLanguage.php')));

interface AblePolecat_QueryLanguage_StatementInterface extends AblePolecat_DynamicObjectInterface {
  
  /**
   * Typical syntax element properties.
   */
  const DDL           = 'ddl';
  const DML           = 'dml';
  const QUERYOBJECT   = 'object';
  const FIELDS        = 'fields';
  const WHERE         = 'where_condition';
  const HAVING        = 'having_condition';
  const GROUPBY       = 'group_by_expression';
  const ORDERBY       = 'order_by_expression';
  const LIMIT         = 'limit';
  const OFFSET        = 'offset';
  const VALUES        = 'values';
  
  /**
   * Typical DML ops (default).
   */
  const SELECT    = 'SELECT';
  
  /**
   * Other constants.
   */
  const LIST_DELIMITER = ', ';
  const NAME_LIST_DELIMITER = '`, `';
  
  /**
   * @return string DML operation.
   */
  public function getDmlOp();
  
  /**
   * @todo: lots on this one, more of a placeholder for now.
   * @todo: something like $Database->quote($this->rvalue())
   * Used to express literal values in DML (for example quotes around strings etc).
   *
   * @param mixed $literal The value of the literal being expressed.
   * @param string $type Data type to override default evaluation.
   *
   * @return string Value of literal expressed in encapsulated SQL DML syntax.
   */
  public static function getLiteralExpression($literal, $type = NULL);
  
  /**
   * Set DML operation.
   * 
   * @param string $DmlOp DML operation.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setDmlOp($DmlOp);
  
  /**
   * Verifies if given syntax element is supported.
   *
   * @param string $dml DML operation (e.g. SELECT, INSERT, etc.)
   * @param string $element One of the predefined SQL syntax element constants.
   *
   * @return bool TRUE if syntax is supported by concrete class, otherwise FALSE.
   */
  public static function supportsSyntax($dml, $element = NULL);
  
  /**
   * @return query langauge statement as a string.
   */
  public function __toString();
}

abstract class AblePolecat_QueryLanguage_StatementAbstract 
  extends AblePolecat_DynamicObjectAbstract 
  implements AblePolecat_QueryLanguage_StatementInterface {
  
  /**
   * @var supported sql syntax.
   */
  private static $supportedSql = NULL;
  
  /**
   * @var string DML operation such as SELECT, INSERT, and so on. Cannot be reset without erasing all properties.
   */
  private $DmlOp;
  
  /********************************************************************************
   * Implementation of AblePolecat_StdObjectInterface.
   ********************************************************************************/
   
  /**
   * Override base class implementation of __set() magic method so as to use syntax checking.
   */
  public function __set($name, $value) {
    switch ($name) {
      default:
        //
        // @todo: do we allow this?
        //
        parent::__set($name, $value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::DML:
        $this->setDmlOp($value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::QUERYOBJECT:
        $this->setQueryObject($value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::FIELDS:
        $this->setFields($value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::WHERE:
        $this->setWhereCondition($value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::HAVING:
        $this->setHavingCondition($value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::GROUPBY:
        $this->setGroupByExpression($value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::ORDERBY:
        $this->setOrderByExpression($value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::LIMIT:
        $this->setLimit($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET:
        $this->setOffset($value);
        break;
      case AblePolecat_QueryLanguage_StatementInterface::VALUES:
        $this->setValues($value);
        break;
    }
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_QueryLanguage_StatementInterface.
   ********************************************************************************/
   
  /**
   * @return string DML operation.
   */
  public function getDmlOp() {
    return $this->DmlOp;
  }
  
  /**
   * @todo: lots on this one, more of a placeholder for now.
   * @todo: something like $Database->quote($this->rvalue())
   * Used to express literal values in DML (for example quotes around strings etc).
   *
   * @param mixed $literal The value of the literal being expressed.
   * @param string $type Data type to override default evaluation.
   *
   * @return string Value of literal expressed in encapsulated SQL DML syntax.
   */
  public static function getLiteralExpression($literal, $type = NULL) {
    
    //
    // @todo: handle NULL values
    //
    $expression = '';
    
    //
    // @todo: handle non-scalar types.
    //
    if (isset($literal) && is_scalar($literal)) {
      switch (gettype($literal)) {
        default:
          //
          // @todo NULL?
          //
          break;
         case 'boolean':
          $literal ? $expression = 'TRUE' : $expression = 'FALSE';
          break;
        case 'integer':
          $expression = intval($literal);
          break;
        case 'double':
          //
          // NOTE: gettype() returns "double" in case of a float, and not simply "float"
          // @todo: obviously must be formatted scale, precision etc.
          //
          $expression = strval($literal);
          break;
        case 'string':
          //
          // NOTE: call to values() handles quotes
          // @see values().
          //
          $expression = strval($literal);
          break;
        case 'NULL':
          //
          // @todo
          //
          break;
      }
    }
    return $expression;
  }
  
  /**
   * Set DML operation.
   * 
   * @param string $DmlOp DML operation.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setDmlOp($DmlOp) {
    
    if (self::supportsSyntax($DmlOp) && !isset($this->DmlOp)) {
      $this->DmlOp = $DmlOp;
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Verifies if given syntax element is supported.
   *
   * @param string $dml DML operation (e.g. SELECT, INSERT, etc.)
   * @param string $element One of the predefined SQL syntax element constants.
   *
   * @return bool TRUE if syntax is supported by concrete class, otherwise FALSE.
   */
  public static function supportsSyntax($dml, $element = NULL) {
    
    $Supported = FALSE;
    
    if (isset($element)) {
      isset(self::$supportedSql[$dml][$element]) ? $Supported = self::$supportedSql[$dml][$element] : $Supported = FALSE;
    }
    else {
      $Supported = isset(self::$supportedSql[$dml]);
    }
    return $Supported;
  }
  
  /********************************************************************************
   * Query building helper functions.
   ********************************************************************************/
  
  public function from() {
    $args = func_get_args();
    isset($args[0]) ? $this->setQueryObject($args[0]) : NULL;
    return $this;
  }
  
  public function group_by() {
    $GroupByExpression = NULL;
    foreach(func_get_args() as $key => $arg) {
      $GroupByExpression .= $arg;
    }
    $this->setGroupByExpression($GroupByExpression);
    return $this;
  }
  
  public function having() {
    $HavingCondition = NULL;
    foreach(func_get_args() as $key => $arg) {
      $HavingCondition .= $arg;
    }
    $this->setHavingCondition($HavingCondition);
    return $this;
  }
  
  public function limit() {
    $args = func_get_args();
    isset($args[0]) ? $this->setLimit($args[0]) : NULL;
    isset($args[1]) ? $this->setOffset($args[1]) : NULL;
    return $this;
  }
  
  public function order_by() {
    $OrderByExpression = NULL;
    foreach(func_get_args() as $key => $arg) {
      $OrderByExpression .= $arg;
    }
    $this->setOrderByExpression($OrderByExpression);
    return $this;
  }
  
  public function select() {
    $Fields = func_get_args();
    $this->setDmlOp(AblePolecat_QueryLanguage_StatementInterface::SELECT);
    $this->setFields($Fields);
    return $this;
  }
  
  public function where() {
    
    $WhereCondition = NULL;
    
    foreach(func_get_args() as $key => $arg) {
      try{
        $strvalue = AblePolecat_Data_Scalar_String::typeCast($arg);
        !isset($WhereCondition) ? $WhereCondition = array() : NULL;
        $WhereCondition[] = $strvalue;
      }
      catch (AblePolecat_Data_Exception $Exception) {
        throw new AblePolecat_QueryLanguage_Exception(
          sprintf("%s WHERE parameter must be scalar or implement __toString(). %s passed.", 
            get_class($this), 
            gettype($arg)
          ), 
          AblePolecat_Error::INVALID_TYPE_CAST
        );
      }
    }
    $this->setWhereCondition(implode(' ', $WhereCondition));
    return $this;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return string QueryObject field list.
   */
  public function getFields() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::FIELDS, NULL);
  }
  
  /**
   * @return string GROUP BY expression.
   */
  public function getGroupByExpression() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::GROUPBY, NULL);
  }
  
  /**
   * @return string HAVING condition.
   */
  public function getHavingCondition() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::HAVING, NULL);
  }
  
  /**
   * @return string LIMIT.
   */
  public function getLimit() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::LIMIT, NULL);
  }
  
  /**
   * @return string Proper syntax for LIMIT/OFFSET.
   */
  public function getLimitOffsetSyntax() {
    
    $Syntax = NULL;
    $Limit = $this->getLimit();
    $Offset = $this->getOffset();
    if (isset($Limit)) {
      $Syntax = "LIMIT $Limit";
      isset($Offset) ? $Syntax .= " OFFSET $Offset" : NULL;
    }
    return $Syntax;
  }
  
  /**
   * @return string OFFSET.
   */
  public function getOffset() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::OFFSET, NULL);
  }
  
  /**
   * @return string ORDER BY expression.
   */
  public function getOrderByExpression() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::ORDERBY, NULL);
  }
  
  /**
   * @return string QueryObject reference.
   */
  public function getQueryObject() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::QUERYOBJECT, NULL);
  }
  
  /**
   * @return string VALUES.
   */
  public function getValues() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::VALUES, NULL);
  }
  
  /**
   * @return string WHERE condition.
   */
  public function getWhereCondition() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_StatementInterface::WHERE, NULL);
  }
  
  /**
   * Set Fields list.
   *
   * @param string Fields Fields list.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setFields($Fields) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_StatementInterface::FIELDS;
    if (self::supportsSyntax($DmlOp, $Element)) {
      if (is_array($Fields)) {
        $delimiter = AblePolecat_QueryLanguage_StatementInterface::NAME_LIST_DELIMITER;
        parent::__set($Element, sprintf("`%s`", implode($delimiter, $Fields)));
      }
      else {
        parent::__set($Element, sprintf("`%s`", strval($Fields)));
      }
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set GROUP BY expression.
   *
   * @param string $GroupByExpression GROUP BY expression.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setGroupByExpression($GroupByExpression) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_StatementInterface::GROUPBY;
    if (self::supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($GroupByExpression));
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set HAVING condition.
   *
   * @param string $HavingCondition HAVING condition.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setHavingCondition($HavingCondition) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_StatementInterface::HAVING;
    if (self::supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($HavingCondition));
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set LIMIT.
   *
   * @param string $Limit LIMIT.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setLimit($Limit) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_StatementInterface::LIMIT;
    if (self::supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, intval($Limit));
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set OFFSET.
   *
   * @param string $Offset OFFSET.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setOffset($Offset) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET;
    if (self::supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, intval($Offset));
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set ORDER BY expression.
   *
   * @param string $OrderByExpression ORDER BY expression.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setOrderByExpression($OrderByExpression) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_StatementInterface::ORDERBY;
    if (self::supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($OrderByExpression));
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set QueryObject references.
   *
   * @param string $QueryObject QueryObject reference.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setQueryObject($QueryObject) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_StatementInterface::QUERYOBJECT;
    if (self::supportsSyntax($DmlOp, $Element)) {
      if (is_scalar($QueryObject)) {
        switch ($DmlOp) {
          default:
            throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp | $Element].",
              AblePolecat_Error::INVALID_SYNTAX);
            break;
          case AblePolecat_QueryLanguage_StatementInterface::SELECT:
            parent::__set($Element, sprintf("`%s`", strval($QueryObject)));
            break;
        }
      }
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set VALUES.
   *
   * @param string $Values VALUES.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setValues($Values) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_StatementInterface::VALUES;
    if (self::supportsSyntax($DmlOp, $Element)) {
      if (is_array($Values)) {
        parent::__set($Element, implode(AblePolecat_QueryLanguage_StatementInterface::LIST_DELIMITER, $Values));
      }
      else {
        parent::__set($Element, strval($Values));
      }
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set WHERE condition.
   *
   * @param string $WhereCondition WHERE condition.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setWhereCondition($WhereCondition) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_StatementInterface::WHERE;
    if (self::supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($WhereCondition));
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Initialize SQL syntax support or override a specific feature.
   *
   * @param string $dml DML operation (e.g. SELECT, INSERT, etc.)
   * @param string $element One of the predefined SQL syntax element constants.
   * @param bool $supported Indicates if given element is supported by class.
   */
  protected static function setSqlSyntaxSupport($dml, $element = NULL, $supported = TRUE) {
    if (!isset(self::$supportedSql)) {
      self::$supportedSql = array();
    }
    !isset(self::$supportedSql[$dml]) ? self::$supportedSql[$dml] = array() : NULL;
    if (isset($element)) {
      $supported ? self::$supportedSql[$dml][$element] = TRUE : self::$supportedSql[$dml][$element] = FALSE;
    }
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {    
    //
    // Initialize SQL support settings (for static method calls).
    //
    self::$supportedSql = array(
      self::SELECT => array(
        self::QUERYOBJECT => TRUE,
        self::FIELDS => TRUE,
        self::WHERE => TRUE,
        self::GROUPBY => TRUE,
        self::HAVING => TRUE,
        self::ORDERBY => TRUE,
        self::LIMIT => TRUE,
        self::OFFSET => TRUE,
      ),
    );
  }
}