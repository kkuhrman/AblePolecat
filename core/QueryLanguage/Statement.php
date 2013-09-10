<?php
/**
 * @file: Statement.php
 * Encapsulates a data definition, manipulation or retrieval query langauge statement.
 *
 * NOTE: intended to abstract cross-purpose use of SQL (e.g. PDO) and OQL (e.g. SOQL).
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'QueryLanguage.php');

//
// Standard SQL SELECT statement
//
// $Statement = array(
//  'select_expression' => array('column_name'), 
//  'table_references' => array('table_name'),
//  'where_condition' => array('conditional syntax'),
//  @todo: 'group_by_expression' => array('group by syntax'),
//  @todo: 'having_condition' => array('having syntax'),
//  'order_by_expression' => array('order by syntax'),
//  'limit' => array('row_count' => integer, 'offset' => integer),
//  no support: PROCEDURE, INTO...
// );
//

interface AblePolecat_QueryLanguage_StatementInterface {
  
  /**
   * Execute the query and return results (rowset).
   *
   * @return Array Results (rowset) of query or FALSE.
   */
  public function getResults();
  
  /**
   * Return more rows from a large result set.
   *
   * Many web services limit number of rows that can be returned in a batch.
   * This function serves same purpose of LIMIT/OFFSET, queryMore() and similar
   * pagination methods.
   *
   * @return Array Results (rowset) of query or FALSE.
   */
  public function getMoreResults();
  
  /**
   * @return string Statement expressed as a string.
   */
  public function __toString();
}