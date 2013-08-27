<?php
/**
 * @file: Service.php
 * Base for web service clients, bus, resources, etc.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');
include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Message.php');

/**
 * Encapsulates a web service.
 */
interface AblePolecat_Service_Interface {
  /**
   * Serialize configuration and connection settings prior to going out of scope.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   */
  public function sleep(AblePolecat_AccessControl_AgentInterface $Agent = NULL);
  
  /**
   * Open a new connection or resume a prior connection.
   *
   * @param AblePolecat_AccessControl_AgentInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Api_ClientAbstract Initialized/connected instance of class ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_AgentInterface $Agent = NULL);
}

/**
 * Manages a client connection to a web services provider.
 */
interface AblePolecat_Service_ClientInterface extends AblePolecat_AccessControl_ArticleInterface, AblePolecat_Service_Interface {
  
  /**
   * Close connection and destroy current session variables relating to connection.
   *
   * @param AblePolecat_AccessControl_AgentInterface $Agent.
   */
  public function close(AblePolecat_AccessControl_AgentInterface $Agent = NULL);
  
  /**
   * Send asynchronous message over client connection.
   *
   * @param AblePolecat_MessageInterface $Message.
   */
  public function dispatch(AblePolecat_MessageInterface $Message);
}

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

/**
 * Manages multiple web services client connections and routes messages
 * between these and the application in scope.
 */
interface AblePolecat_Service_BusInterface extends AblePolecat_Service_ClientInterface {
}

/**
  * Exceptions thrown by Able Polecat data sub-classes.
  */
class AblePolecat_Service_Exception extends AblePolecat_Exception {
}