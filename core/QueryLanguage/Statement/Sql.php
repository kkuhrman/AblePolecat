<?php
/**
 * @file: Sql.php
 * Base class for most SQL statement objects.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'QueryLanguage', 'Statement.php')));
require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Overloadable.php');

interface AblePolecat_QueryLanguage_Statement_Sql_Interface extends AblePolecat_ArgsListInterface, 
  AblePolecat_OverloadableInterface, 
  AblePolecat_QueryLanguage_StatementInterface {
    
    /**
     * SQL syntax element properties.
     */
    const TABLES      = 'tables';
    const COLUMNS     = 'columns';
    const WHERE       = 'where_condition';
    const HAVING      = 'having_condition';
    const GROUPBY     = 'group_by_expression';
    const ORDERBY     = 'order_by_expression';
    const LIMIT       = 'limit';
    const OFFSET      = 'offset';
    const VALUES      = 'values';
    
    /**
     * Verifies if given syntax element is supported.
     *
     * @param string $element One of the predefined SQL syntax element constants.
     *
     * @return bool TRUE if syntax is supported by concrete class, otherwise FALSE.
     */
    public static function supportsSyntax($element);
}

abstract class AblePolecat_QueryLanguage_Statement_SqlAbstract extends AblePolecat_ArgsListAbstract implements AblePolecat_QueryLanguage_Statement_Sql_Interface {
  
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
    
    $ArgsList = AblePolecat_ArgsList_Overloaded::create();
    
    foreach($args as $key => $value) {
      switch ($method_name) {
        default:
          break;
        case '__construct':
          switch($key) {
            case 0:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::TABLES} = $value;
              break;
            case 1:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::COLUMNS} = $value;
              break;
            case 2:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::WHERE} = $value;
              break;
            case 3:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::ORDERBY} = $value;
              break;
            case 4:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::VALUES} = $value;
              break;
            case 5:
              $ArgsList->{AblePolecat_QueryLanguage_Statement_Sql_Interface::LIMIT} = $value;
              break;
          }
          break;
      }
    }
    return $ArgsList;
  }
}