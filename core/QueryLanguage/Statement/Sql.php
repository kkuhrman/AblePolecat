<?php
/**
 * @file      polecat/core/QueryLanguage/Statement/Sql.php
 * @brief     Base class for most SQL statement objects.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Statement.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Expression', 'Binary', 'Sql.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Overloadable.php');

interface AblePolecat_QueryLanguage_Statement_Sql_Interface extends AblePolecat_OverloadableInterface, 
  AblePolecat_QueryLanguage_StatementInterface {
    
  /**
   * Extended SQL syntax element properties.
   */
  const TABLES        = 'tables';
  const COLUMNS       = 'columns';
  const INNER_JOIN    = 'join';
  const DATABASE_NAME = 'dbname';
    
  /**
   * Supported utility statements.
   */
  const USEDB       = 'USEDB';
  
  /**
   * Extended DML ops.
   */
  const INSERT    = 'INSERT';
  const DELETE    = 'DELETE';
  const REPLACE   = 'REPLACE';
  const UPDATE    = 'UPDATE';
  
  /**
   * @return string Name of database against which to execute statement.
   */
  public function getDatabaseName();
}

abstract class AblePolecat_QueryLanguage_Statement_SqlAbstract extends AblePolecat_QueryLanguage_StatementAbstract implements AblePolecat_QueryLanguage_Statement_Sql_Interface {
  
  /**
   * @var string Name of database against which to execute statement.
   */
  private $databaseName;
  
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
      // case AblePolecat_QueryLanguage_Statement_Sql_Interface::DML:
        // $this->setDmlOp($value);
        // break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES:
        $this->setTables($value);
        break;
      case AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS:
        $this->setColumns($value);
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
   * @return string Name of database against which to execute statement.
   */
  public function getDatabaseName() {
    return $this->databaseName;
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
   * Set name of database against which to execute statement 
   *
   * @param string $databaseName Name of database.
   *
   * @throw AblePolecat_QueryLanguage_Exception if syntax is not supported.
   */
  public function setDatabaseName($databaseName) {
    $this->databaseName = $databaseName;
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
    if (self::supportsSyntax($DmlOp, $Element)) {
      //
      // @todo: handle aliasing
      //
      if (is_array($Tables)) {
        switch ($DmlOp) {
          default:
            if ($this->encloseObjectNames()) {
              parent::__set($Element, sprintf("`%s`", implode(AblePolecat_QueryLanguage_Statement_Sql_Interface::NAME_LIST_DELIMITER, $Tables)));
            }
            else {
              parent::__set($Element, sprintf("%s", implode(AblePolecat_QueryLanguage_Statement_Sql_Interface::LIST_DELIMITER, $Tables)));
            }
            break;
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::INSERT:
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::REPLACE:
          case AblePolecat_QueryLanguage_Statement_Sql_Interface::DELETE:
            //
            // If parameter is single-element array, allow use of first element.
            // Otherwise, complain...
            //
            if (count($Tables) == 1) {
              if ($this->encloseObjectNames()) {
                parent::__set($Element, sprintf("`%s`", strval($Tables[0])));
              }
              else {
                parent::__set($Element, sprintf("%s", strval($Tables[0])));
              }
            }
            else {
              throw new AblePolecat_QueryLanguage_Exception("Invalid SQL syntax [$DmlOp]. Only one table can be referenced. " . count($Tables) . " given.",
                AblePolecat_Error::INVALID_SYNTAX);
            }
            break;
        }
      }
      else {
        if ($this->encloseObjectNames()) {
          parent::__set($Element, sprintf("`%s`", strval($Tables)));
        }
        else {
          parent::__set($Element, sprintf("%s", strval($Tables)));
        }
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
        $delimiter = AblePolecat_QueryLanguage_Statement_Sql_Interface::NAME_LIST_DELIMITER;
        switch($DmlOp) {
          default:
            if ($this->encloseObjectNames()) {
              parent::__set($Element, sprintf("`%s`", implode($delimiter, $Columns)));
            }
            else {
              parent::__set($Element, sprintf("%s", implode($delimiter, $Columns)));
            }
            break;
          case self::UPDATE:
            $delimiter = AblePolecat_QueryLanguage_Statement_Sql_Interface::LIST_DELIMITER;
            parent::__set($Element, sprintf("%s", implode($delimiter, $Columns)));
            break;
        }
      }
      else {
        if ($this->encloseObjectNames()) {
          parent::__set($Element, sprintf("`%s`", strval($Columns)));
        }
        else {
          parent::__set($Element, sprintf("%s", strval($Columns)));
        }
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
    if (self::supportsSyntax($DmlOp, $Element)) {
      parent::__set($Element, strval($JoinStatement));
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
    if (self::supportsSyntax($DmlOp, $Element)) {
      switch ($DmlOp) {
        default:
          parent::setValues($Values);
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
              if ($this->encloseObjectNames()) {
                $setExpr[] = sprintf("`%s` = %s",
                  $name,
                  $this->getLiteralExpression($Values[$key])
                );
              }
              else {
                $setExpr[] = sprintf("%s = %s",
                  $name,
                  $this->getLiteralExpression($Values[$key])
                );
              }
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
      throw new AblePolecat_QueryLanguage_Exception("Invalid SQL Syntax [$DmlOp | $Element].",
        AblePolecat_Error::INVALID_SYNTAX);
    }
  }
  
  /********************************************************************************
   * Query building helper functions.
   ********************************************************************************/
  
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
        $JoinStatement[] = AblePolecat_Data_Primitive_Scalar_String::typeCast($arg);
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
  
  public function usedb() {
    $Values = func_get_args();
    if (isset($Values[0]) && is_string($Values[0])) {
      $this->databaseName = $Values[0];
    }
    return $this;
  }
  
  public function values() {
    $Values = func_get_args();
    $ValuesQuotes = array();
    foreach($Values as $key => $value) {
      //
      // @todo: obviously inadequate - need a better solution involving driver/schema & checking
      //
      $ValuesQuotes[] = sprintf("'%s'", str_replace(array('\\', '\''), array('\\\\', '\'\''), $value));
    }
    $this->setValues($ValuesQuotes);
    return $this;
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
    parent::setSqlSyntaxSupport(self::SELECT, self::TABLES, TRUE);
    parent::setSqlSyntaxSupport(self::SELECT, self::COLUMNS, TRUE);
    // parent::setSqlSyntaxSupport(self::SELECT, self::WHERE, TRUE);
    // parent::setSqlSyntaxSupport(self::SELECT, self::GROUPBY, TRUE);
    // parent::setSqlSyntaxSupport(self::SELECT, self::HAVING, TRUE);
    // parent::setSqlSyntaxSupport(self::SELECT, self::ORDERBY, TRUE);
    // parent::setSqlSyntaxSupport(self::SELECT, self::LIMIT, TRUE);
    parent::setSqlSyntaxSupport(self::SELECT, self::OFFSET, TRUE);
    parent::setSqlSyntaxSupport(self::SELECT, self::INNER_JOIN, TRUE);
    parent::setSqlSyntaxSupport(self::INSERT, self::TABLES, TRUE);
    parent::setSqlSyntaxSupport(self::INSERT, self::COLUMNS, TRUE);
    parent::setSqlSyntaxSupport(self::INSERT, self::VALUES, TRUE);
    parent::setSqlSyntaxSupport(self::REPLACE, self::TABLES, TRUE);
    parent::setSqlSyntaxSupport(self::REPLACE, self::COLUMNS, TRUE);
    parent::setSqlSyntaxSupport(self::REPLACE, self::VALUES, TRUE);
    parent::setSqlSyntaxSupport(self::UPDATE, self::TABLES, TRUE);
    parent::setSqlSyntaxSupport(self::UPDATE, self::COLUMNS, TRUE);
    parent::setSqlSyntaxSupport(self::UPDATE, self::WHERE, TRUE);
    parent::setSqlSyntaxSupport(self::UPDATE, self::ORDERBY, TRUE);
    parent::setSqlSyntaxSupport(self::UPDATE, self::LIMIT, TRUE);
    parent::setSqlSyntaxSupport(self::UPDATE, self::OFFSET, TRUE);
    parent::setSqlSyntaxSupport(self::UPDATE, self::VALUES, TRUE);
    parent::setSqlSyntaxSupport(self::DELETE, self::TABLES, TRUE);
    parent::setSqlSyntaxSupport(self::DELETE, self::WHERE, TRUE);
    parent::setSqlSyntaxSupport(self::DELETE, self::ORDERBY, TRUE);
    parent::setSqlSyntaxSupport(self::DELETE, self::LIMIT, TRUE);
    parent::setSqlSyntaxSupport(self::DELETE, self::OFFSET, TRUE);
    $this->databaseName = NULL;
  }
}

/**
 * A vanialla-type SQL wrapper with several helper methods.
 */
function __SQL() {
  
  $args = func_get_args();
  $options = array();
  if (isset($args[0]) && is_array($args[0])) {
    $options = $args[0];
  }
  $Query = AblePolecat_Sql::create();
  isset($options['encloseObjectNames']) ? $Query->setEncloseObjectNames($options['encloseObjectNames']) : NULL;
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
   */
  protected function initialize() {
    parent::initialize();
    $this->setEncloseObjectNames(TRUE);
  }
}