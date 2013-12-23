<?php
/**
 * @file: core/CacheObject/Pdo.php
 * Any object, which uses the PDO/core database to maintain state.
 */
 
include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
 
abstract class AblePolecat_CacheObject_PdoAbstract extends AblePolecat_CacheObjectAbstract {
  
  /**
   * @var AblePolecat_Database_Pdo Connection to application database.
   */
  private $Database;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    //
    // Check db connection
    //
    $this->Database = AblePolecat_Server::getDatabase();
  }
  
  /**
   * Execute SQL and return results.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql
   *
   * @return Array Results/rowset.
   */
  protected function executeStatement(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql) {
    
    $Results = array();
    
    if (isset($this->Database)) {
      $Stmt = $this->Database->prepareStatement($sql);
      if ($Stmt->execute()) {
        while ($result = $Stmt->fetch()) {
          $Results[] = $result;
        }
      }
    }
    return $Results;
  }
}