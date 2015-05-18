<?php
/**
 * @file      polecat/core/Service/Dtx.php
 * @brief     Base class for data transformation and exchange web services.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Environment.php');
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Service.php');

/**
 * Encapsulates a service for transforming and exchanging data between two clients.
 */
interface AblePolecat_Service_DtxInterface extends AblePolecat_Service_Interface {
  
  /**
   * Sanitize, transform and normalize raw data into target records and exchange.
   *
   * @return Array Responses from target client or FALSE if target client does not respond.
   */
  public function exchange();
  
  /**
   * Execute internal query and load initial page/batch of data into memory.
   *
   * @return Array Rows of raw data from source or FALSE if query returned no records.
   */
  public function load();
  
  /**
   * Execute internal query and load next page/batch of data into memory.
   *
   * @return Array Rows of raw data from source or FALSE if query returned no records.
   */
  public function loadMore();
  
  /**
   * Prepare a valid SQL SELECT statement for the *source* database and store internally.
   *
   * @param Array or String $select_expression List of column names or valid SELECT expression
   * @param string $table_name table name or references
   * @param string $where_condition WHERE condition
   * @param string $group_by_expression GROUP BY expression
   * @param string $order_by_expression ORDER BY expression
   * @param Array $options Options are driver-specific.
   *
   * @return TRUE if SELECT statement is ready for execution, otherwise FALSE.
   * @see prepare().
   */
  public function prepare(
    $select_expression,
    $table_name,
    $where_condition = NULL,
    $group_by_expression = NULL,
    $order_by_expression = NULL,
    $options = NULL
  );
}

abstract class AblePolecat_Service_DtxAbstract implements AblePolecat_Service_DtxInterface {
  
  /**
   * @var string m_table_name 
   */
  private $m_table_name;
  
  /**
   * @var string The parameterized query string
   */
  private $m_query;
  
  /**
   * @var string Query for getting COUNT of all records meeting WHERE condition.
   */
  private $m_CountQuery;
  
  /********************************************************************************
   * Implementation of AblePolecat_Service_DtxInterface.
   ********************************************************************************/
  
  /**
   * Prepare a valid SQL SELECT statement for the *source* database and store internally.
   *
   * @param Array or String $select_expression List of column names or valid SELECT expression
   * @param string $table_name table name or references
   * @param string $where_condition WHERE condition
   * @param string $group_by_expression GROUP BY expression
   * @param string $order_by_expression ORDER BY expression
   * @param Array $options Options are driver-specific.
   *
   * @return TRUE if SELECT statement is ready for execution, otherwise FALSE.
   * @see prepare().
   */
  public function prepare(
    $select_expression,
    $table_name,
    $where_condition = NULL,
    $group_by_expression = NULL,
    $order_by_expression = NULL,
    $options = NULL
  ) {
    
    $queryReady = TRUE;
    
    //
    // These functions will build the query string
    //
    try {
      $this->setSelectColumns($select_columns);
      $this->setTableName($table_name);
      $this->setWhereCondition($where_condition);
      $this->setGroupByExpression($group_by_expression);
      $this->setOrderByExpression($order_by_expression);
      $this->setQueryOptions($options);
    }
    catch(Exception $Exception) {
      $queryReady = FALSE;
      //
      // @todo log exception message
      //
    }
    return $queryReady;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @todo: check proper formatting/escaping of table name
   */
  protected function setTableName($table_name) {
    $this->m_table_name = $table_name;
    $this->m_query .= " FROM " . $this->m_table_name;
    $this->m_CountQuery .= " FROM " . $this->m_table_name;
  }
  
  /**
   * @todo: check proper formating/escaping of column name(s)
   * @todo: check syntax of sub queries
   */
  protected function setSelectColumns($select_columns) {
    
    if (isset($select_columns)) {
      if (is_array($select_columns)) {
        //
        // @todo: check that expression is string, escape it, blah, blah, blah
        // @todo: deal with aliasing where syntax allows it e.g. $expression AS $alias
        //
        $this->m_query = "SELECT " . implode(', ', $select_columns);
      }
      else if (is_string($select_columns)) {
        $this->m_query = "SELECT $select_columns";
      }
    }
    else {
      throw new AblePolecat_Service_Exception("Query contains no SELECT expression", 
            AblePolecat_Service_Exception::QUERY_SYNTAX_ERROR);
    }
  }
  
  /**
   * @todo: check proper syntax of WHERE condition
   */
  protected function setWhereCondition($where_condition) {
    if (isset($where_condition) && is_string($where_condition)) {
      $this->m_query .= " $where_condition ";
      $this->m_CountQuery .= " $where_condition ";
    }
  }
  
  /**
   * @todo: check proper syntax of GROUP BY expression
   */
  protected function setGroupByExpression($group_by_expression) {
    if (isset($group_by_expression) && is_string($group_by_expression)) {
      $this->m_query .= " $group_by_expression "; 
    }
  }
  
  /**
   * @todo: check proper syntax of ORDER BY expression
   */
  protected function setOrderByExpression($order_by_expression) {
    if (isset($order_by_expression) && is_string($order_by_expression)) {
      $this->m_query .= " $order_by_expression ";
    }
  }
  
  /**
   * Set driver-specific options during preparation of internal query.
   *
   * @param Array $options Sub-classes should override if options are to be passed.
   */
  protected function setQueryOptions($options) {
  }
  
  /**
   * @return Prepared SQL statement.
   */
  public function getQuery() {
    return $this->m_query;
  }
  
  /**
   * Extends __construct(). 
   * 
   * Sub-classes can override to initialize members such as service clients.
   */
  protected function initialize() {
    $this->m_query = NULL;
    $this->m_table_name = NULL;
    $this->m_CountQuery = 'SELECT COUNT(*)';
  }
}