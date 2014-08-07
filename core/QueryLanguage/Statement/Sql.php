<?php
/**
 * @file      polecat/core/QueryLanguage/Statement/Sql.php
 * @brief     Base class for most SQL statement objects.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Statement.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Expression', 'Binary', 'Sql.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar', 'String.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Overloadable.php');

interface AblePolecat_QueryLanguage_Statement_Sql_Interface extends AblePolecat_DynamicObjectInterface, 
  AblePolecat_OverloadableInterface, 
  AblePolecat_QueryLanguage_StatementInterface {
    
    /**
     * SQL syntax element properties.
     */
    const DML         = 'dml';
    const TABLES      = 'tables';
    const COLUMNS     = 'columns';
    const WHERE       = 'where_condition';
    const HAVING      = 'having_condition';
    const GROUPBY     = 'group_by_expression';
    const ORDERBY     = 'order_by_expression';
    const LIMIT       = 'limit';
    const OFFSET      = 'offset';
    const VALUES      = 'values';
    const INNER_JOIN  = 'join';
    
    /**
   * Supported DML ops (default).
   */
  const SELECT    = 'SELECT';
  const INSERT    = 'INSERT';
  const REPLACE   = 'REPLACE';
  const UPDATE    = 'UPDATE';
  const DELETE    = 'DELETE';
  
  /**
   * Other constants.
   */
  const LIST_DELIMITER = ', ';
    
    /**
     * Verifies if given syntax element is supported.
     *
     * @param string $dml DML operation (e.g. SELECT, INSERT, etc.)
     * @param string $element One of the predefined SQL syntax element constants.
     *
     * @return bool TRUE if syntax is supported by concrete class, otherwise FALSE.
     */
    public static function supportsSyntax($dml, $element = NULL);
}

abstract class AblePolecat_QueryLanguage_Statement_SqlAbstract extends AblePolecat_DynamicObjectAbstract implements AblePolecat_QueryLanguage_Statement_Sql_Interface {
  
  /**
   * @var string DML operation such as SELECT, INSERT, and so on. Cannot be reset without erasing all properties.
   */
  private $DmlOp;
  
  /**
   * @var supported sql syntax.
   */
  private static $supportedSql = NULL;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
   
  /**
   * Override base class implementation of __set() magic method so as to use syntax checking.
   */
  public function __set($name, $value) {
    
    switch ($name) {
      default:
        parent::__set($name, $value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::DML:
        $this->setDmlOp($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES:
        $this->setTables($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS:
        $this->setColumns($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE:
        $this->setWhereCondition($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::HAVING:
        $this->setHavingCondition($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::GROUPBY:
        $this->setGroupByExpression($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY:
        $this->setOrderByExpression($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT:
        $this->setLimit($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET:
        $this->setOffset($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES:
        $this->setValues($value);
        break;
    }
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_OverloadableInterface.
   ********************************************************************************/
  
  /**
   * Marshall numeric-indexed array of variable method arguments.
   *
   * @param string $method_name __METHOD__ will render className::methodName; __FUNCTION__ is probably good enough.
   * @param Array $args Variable list of arguments passed to method (i.e. get_func_args()).
   * @param mixed $options Reserved for future use.
   *
   * @return Array Associative array representing [argument name] => [argument value]
   */
  public static function unmarshallArgsList($method_name, $args, $options = NULL) {
    
    $ArgsList = AblePolecat_ArgsList::create();
    
    foreach($args as $key => $value) {
      switch ($method_name) {
        default:
          break;
        case 'create':
          switch($key) {
            case 0:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::DML} = $value;
              break;
            case 1:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES} = $value;
              break;
            case 2:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS} = $value;
              break;
            case 3:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE} = $value;
              break;
            case 4:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY} = $value;
              break;
            case 5:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES} = $value;
              break;
            case 6:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT} = $value;
              break;
            case 7:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET} = $value;
              break;
            case 8:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::GROUPBY} = $value;
              break;
            case 9:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::HAVING} = $value;
              break;
          }
          break;
      }
    }
    return $ArgsList;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_QueryLanguage_StatementInterface.
   ********************************************************************************/
  
  /**
   * @return query langauge statement as a string.
   */
  public function __toString() {
    
    $sqlStatement = '';
    $tokens = array($this->getDmlOp());
    switch($this->getDmlOp()) {
      default:
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::SELECT:
        $tokens[] = $this->getColumns();
        if ($this->getTables()) {
          $tokens[] = 'FROM';
          $tokens[] = $this->getTables();
        }
        if ($this->getJoinStatement()) {
          $tokens[] = 'JOIN';
          $tokens[] = $this->getJoinStatement();
        }
        if ($this->getWhereCondition()) {
          $tokens[] = 'WHERE';
          $tokens[] = $this->getWhereCondition();
        }
        if ($this->getGroupByExpression()) {
          $tokens[] = 'GROUP BY';
          $tokens[] = $this->getGroupByExpression();
        }
        if ($this->getHavingCondition()) {
          $tokens[] = 'HAVING';
          $tokens[] = $this->getHavingCondition();
        }
        if ($this->getOrderByExpression()) {
          $tokens[] = 'ORDER BY';
          $tokens[] = $this->getOrderByExpression();
        }
        $tokens[] = $this->getLimitOffsetSyntax();
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::INSERT:
        if ($this->getTables()) {
          $tokens[] = 'INTO';
          $tokens[] = $this->getTables();
        }
        $tokens[] = '(' . $this->getColumns() . ')';
        $tokens[] = 'VALUES (' . $this->getValues() . ')';
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::REPLACE:
        if ($this->getTables()) {
          $tokens[] = 'INTO';
          $tokens[] = $this->getTables();
        }
        $tokens[] = '(' . $this->getColumns() . ')';
        $tokens[] = 'VALUES (' . $this->getValues() . ')';
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::UPDATE:
        $tokens[] = $this->getTables();
        $tokens[] = 'SET';
        $tokens[] = $this->getColumns();
        if ($this->getWhereCondition()) {
          $tokens[] = 'WHERE';
          $tokens[] = $this->getWhereCondition();
        }
        if ($this->getOrderByExpression()) {
          $tokens[] = 'ORDER BY';
          $tokens[] = $this->getOrderByExpression();
        }
        $tokens[] = $this->getLimitOffsetSyntax();
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::DELETE:
        if ($this->getTables()) {
          $tokens[] = 'FROM';
          $tokens[] = $this->getTables();
        }
        if ($this->getWhereCondition()) {
          $tokens[] = 'WHERE';
          $tokens[] = $this->getWhereCondition();
        }
        if ($this->getOrderByExpression()) {
          $tokens[] = 'ORDER BY';
          $tokens[] = $this->getOrderByExpression();
        }
        $tokens[] = $this->getLimitOffsetSyntax();
        break;
    }
    $sqlStatement = implode(' ', $tokens);
    return trim($sqlStatement);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_QueryLanguage_Statement_Sql_Interface.
   ********************************************************************************/
  
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
    
    //
    // Initialize SQL support settings (for static method calls).
    //
    self::setSqlSyntaxSupport();
    
    if (isset($element)) {
      isset(self::$supportedSql[$dml][$element]) ? $Supported = self::$supportedSql[$dml][$element] : $Supported = FALSE;
    }
    else {
      $Supported = isset(self::$supportedSql[$dml]);
    }
    return $Supported;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Initialize class from creational method variable args list.
   * 
   * @param AblePolecat_ArgsListInterface $ArgsList
   * @see unmarshallArgsList().
   */
  protected function populateFromArgsList(AblePolecat_ArgsListInterface $ArgsList) {
    
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::DML}) ? $this->setDmlOp($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::DML}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES}) ? $this->setTables($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS}) ? $this->setColumns($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE}) ? $this->setWhereCondition($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY}) ? $this->setOrderByExpression($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES}) ? $this->setValues($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT}) ? $this->setLimit($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET}) ? $this->setOffset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::GROUPBY}) ? $this->setGroupByExpression($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::GROUPBY}) : NULL;
    isset($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::HAVING}) ? $this->setHavingCondition($ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::HAVING}) : NULL;
  }
  
  /**
   * Initialize SQL syntax support or override a specific feature.
   *
   * @param string $dml DML operation (e.g. SELECT, INSERT, etc.)
   * @param string $element One of the predefined SQL syntax element constants.
   * @param bool $supported Indicates if given element is supported by class.
   */
  protected static function setSqlSyntaxSupport($dml = NULL, $element = NULL, $supported = TRUE) {
    
    if (!isset(self::$supportedSql)) {
      self::$supportedSql = array(
        AblePolecat_QueryLanguage_Statement_Sql_Interface::SELECT => array(
          AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::GROUPBY => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::HAVING => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::INNER_JOIN => TRUE,
        ),
        AblePolecat_QueryLanguage_Statement_Sql_Interface::INSERT => array(
          AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES => TRUE,
        ),
        AblePolecat_QueryLanguage_Statement_Sql_Interface::REPLACE => array(
          AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES => TRUE,
        ),
        AblePolecat_QueryLanguage_Statement_Sql_Interface::UPDATE => array(
          AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES => TRUE,
        ),
        AblePolecat_QueryLanguage_Statement_Sql_Interface::DELETE => array(
          AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT => TRUE,
          AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET => TRUE,
        ),
      );
    }
    if (isset($dml)) {
      !isset(self::$supportedSql[$dml]) ? self::$supportedSql[$dml] = array() : NULL;
      if (isset($element)) {
        $supported ? self::$supportedSql[$dml][$element] = TRUE : self::$supportedSql[$dml][$element] = FALSE;
      }
    }
  }
  
  /**
   * @todo: lots on this one, more of a placeholder for now.
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
   * @return string DML operation.
   */
  public function getDmlOp() {
    return $this->DmlOp;
  }
  
  /**
   * @return string Table references.
   */
  public function getTables() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES, NULL);
  }
  
  /**
   * @return string Column list.
   */
  public function getColumns() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS, NULL);
  }
  
  /**
   * @return string INNER JOIN statement.
   */
  public function getJoinStatement() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::INNER_JOIN, NULL);
  }
  
  /**
   * @return string WHERE condition.
   */
  public function getWhereCondition() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE, NULL);
  }
  
  /**
   * @return string HAVING condition.
   */
  public function getHavingCondition() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::HAVING, NULL);
  }
  
  /**
   * @return string GROUP BY expression.
   */
  public function getGroupByExpression() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::GROUPBY, NULL);
  }
  
  /**
   * @return string ORDER BY expression.
   */
  public function getOrderByExpression() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY, NULL);
  }
  
  /**
   * @return string LIMIT.
   */
  public function getLimit() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT, NULL);
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
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::OFFSET, NULL);
  }
  
  /**
   * @return string VALUES.
   */
  public function getValues() {
    return $this->getPropertyValue(AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES, NULL);
  }
  
  /**
   * Set DML operation.
   * 
   * @param string $DmlOp DML operation.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setDmlOp($DmlOp) {
    
    if ($this->supportsSyntax($DmlOp) && !isset($this->DmlOp)) {
      $this->DmlOp = $DmlOp;
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set Table references.
   *
   * @param string $Tables Table references.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setTables($Tables) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES;
    if ($this->supportsSyntax($DmlOp, $Element)) {
      //
      // @todo: handle aliasing
      //
      if (is_array($Tables)) {
        switch ($DmlOp) {
          default:
            parent::__set($Element, implode(AblePolecat_QueryLanguage_Statement_Sql_Interface::LIST_DELIMITER, $Tables));
            break;
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::INSERT:
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::REPLACE:
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::DELETE:
            //
            // If parameter is single-element array, allow use of first element.
            // Otherwise, complain...
            //
            if (count($Tables) == 1) {
              parent::__set($Element, strval($Tables[0]));
            }
            else {
              throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp]. Only one table can be referenced. " . count($Tables) . " given.",
                AblePolecat_Error::INVALID_SYNTAX);
            }
            break;
        }
      }
      else {
        parent::__set($Element, strval($Tables));
      }
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set Column list.
   *
   * @param string Columns Column list.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setColumns($Columns) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS;
    if ($this->supportsSyntax($DmlOp, $Element)) {
      if (is_array($Columns)) {
        parent::__set($Element, implode(AblePolecat_QueryLanguage_Statement_Sql_Interface::LIST_DELIMITER, $Columns));
      }
      else {
        parent::__set($Element, strval($Columns));
      }
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /**
   * Set INNER JOIN statement.
   *
   * @param string $JoinStatement JOIN condition.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setJoinStatement($JoinStatement) {
    
    $DmlOp = $this->getDmlOp();
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::INNER_JOIN;
    if ($this->supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($JoinStatement));
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
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE;
    if ($this->supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($WhereCondition));
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
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::HAVING;
    if ($this->supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($HavingCondition));
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
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
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::GROUPBY;
    if ($this->supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($GroupByExpression));
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
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY;
    if ($this->supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($OrderByExpression));
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
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT;
    if ($this->supportsSyntax($DmlOp, $Element)) {
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
    if ($this->supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, intval($Offset));
    }
    else {
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
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
    $Element = AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES;
    if ($this->supportsSyntax($DmlOp, $Element)) {
      if (is_array($Values)) {
        switch ($DmlOp) {
          default:
            parent::__set($Element, implode(AblePolecat_QueryLanguage_Statement_Sql_Interface::LIST_DELIMITER, $Values));
            break;
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::UPDATE:
            //
            // This parameter may be used for UPDATE operations to pass
            // column names and set values as separate arrays.
            //
            $columns = explode(AblePolecat_QueryLanguage_Statement_Sql_Interface::LIST_DELIMITER, $this->getColumns());
            
            //
            // Column names and values counts must match
            //
            if (count($columns) === count($Values)) {
              //
              // Render column SET expressions
              //
              $setExpr = array();
              foreach($columns as $key => $name) {
                //
                // @todo: handle quoting
                //
                $setExpr[] = sprintf("%s = %s",
                  $name,
                  $this->getLiteralExpression($Values[$key])
                );
              }
              //
              // Overwrite column names
              //
              $this->setColumns($setExpr);
            }
            else {
              throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element]. Number of SET values must match number of UPDATE columns.",
                AblePolecat_Error::INVALID_SYNTAX);
            }
            break;
        }
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
   * Helper functions.
   */
  public function delete() {
    $this->setDmlOp(AblePolecat_QueryLanguage_Statement_Sql_Interface::DELETE);
    return $this;
  }
  
  public function select() {
    $Columns = func_get_args();
    $this->setDmlOp(AblePolecat_QueryLanguage_Statement_Sql_Interface::SELECT);
    $this->setColumns($Columns);
    return $this;
  }
  
  public function from() {
    $Tables = func_get_args();
    $this->setTables($Tables);
    return $this;
  }
  
  public function insert() {
    $Columns = func_get_args();
    $this->setDmlOp(AblePolecat_QueryLanguage_Statement_Sql_Interface::INSERT);
    $this->setColumns($Columns);
    return $this;
  }
  
  public function replace() {
    $Columns = func_get_args();
    $this->setDmlOp(AblePolecat_QueryLanguage_Statement_Sql_Interface::REPLACE);
    $this->setColumns($Columns);
    return $this;
  }
  
  public function into() {
    $Tables = func_get_args();
    $this->setTables($Tables);
    return $this;
  }
  
  public function update() {
    $Tables = func_get_args();
    $this->setDmlOp(AblePolecat_QueryLanguage_Statement_Sql_Interface::UPDATE);
    $this->setTables($Tables);
    return $this;
  }
  
  public function set() {
    $Columns = func_get_args();
    $this->setColumns($Columns);
    return $this;
  }
  
  public function join() {
    
    $JoinStatement = NULL;
    
    foreach(func_get_args() as $key => $arg) {
      try{
        !isset($JoinStatement) ? $JoinStatement = array() : NULL;
        $JoinStatement[] = AblePolecat_Data_Scalar_String::typeCast($arg);
        ($key === 0) ? $JoinStatement[] = "ON" : NULL;
      }
      catch (AblePolecat_Data_Exception $Exception) {
        throw new AblePolecat_QueryLanguage_Exception(
          sprintf("%s INNER JOIN parameter must be scalar or implement __toString(). %s passed.", 
            get_class($this), 
            gettype($arg)
          ), 
          AblePolecat_Error::INVALID_TYPE_CAST
        );
      }
    }
    $this->setJoinStatement(implode(' ', $JoinStatement));
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
  
  public function order_by() {
    $OrderByExpression = NULL;
    foreach(func_get_args() as $key => $arg) {
      $OrderByExpression .= $arg;
    }
    $this->setOrderByExpression($OrderByExpression);
    return $this;
  }
  
  public function limit() {
    $args = func_get_args();
    isset($args[0]) ? $this->setLimit($args[0]) : NULL;
    isset($args[1]) ? $this->setOffset($args[1]) : NULL;
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
  
  public function values() {
    $Values = func_get_args();
    $ValuesQuotes = array();
    foreach($Values as $key => $value) {
      //
      // @todo: obviously inadequate - need a better solution involving driver/schema & checking
      //
      $ValuesQuotes[] = sprintf("'%s'", str_replace('\'', '\'\'', $value));
    }
    $this->setValues($ValuesQuotes);
    return $this;
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    
    // if (!AblePolecat_Server::getClassRegistry()->isLoadable('AblePolecat_QueryLanguage_Expression_Binary_Sql')) {
      // AblePolecat_Server::getClassRegistry()->registerLoadableClass(
        // 'AblePolecat_QueryLanguage_Expression_Binary_Sql', 
        // implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Expression', 'Binary', 'Sql.php')),
        // '__construct'
      // );
    // }
    // if (!AblePolecat_Server::getClassRegistry()->isLoadable('AblePolecat_Data_Scalar_String')) {
      // AblePolecat_Server::getClassRegistry()->registerLoadableClass(
        // 'AblePolecat_Data_Scalar_String', 
        // implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar', 'String.php')),
        // 'typeCast'
      // );
    // }
    
    //
    // Initialize SQL support settings (for static method calls).
    //
    self::setSqlSyntaxSupport();
  }
}

/**
 * A vanialla-type SQL wrapper with several helper methods.
 */
function __SQL() {
  $Query = AblePolecat_Sql::create();
  return $Query;
}

/**
 * Helper function - create a SQL expression.
 */
function __SQLEXPR() {
  
  $num = func_num_args();
  $Expression = NULL;
  
  switch ($num) {
    default:
      break;
    case 3:
      $args = func_get_args();
      $Expression = new AblePolecat_QueryLanguage_Expression_Binary_Sql($args[0], $args[1], $args[2]);
      break;
  }
  return $Expression;
}

class AblePolecat_Sql extends AblePolecat_QueryLanguage_Statement_SqlAbstract {
  
  /********************************************************************************
   * Implementation of AblePolecat_QueryLanguage_StatementInterface.
   ********************************************************************************/
   
  public static function create() {
    //
    // Create a new query object.
    //
    $Query = new AblePolecat_Sql();
    
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    $Query->populateFromArgsList($ArgsList);
    
    //
    // Return initialized object.
    //
    return $Query;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}