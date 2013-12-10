<?php
/**
 * @file: Sql.php
 * Interface for a binary SQL expression.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Expression', 'Binary.php')));

interface AblePolecat_QueryLanguage_Expression_Binary_SqlInterface extends AblePolecat_QueryLanguage_Expression_BinaryInterface {
}

class AblePolecat_QueryLanguage_Expression_Binary_Sql extends AblePolecat_QueryLanguage_Expression_BinaryAbstract {
  
  /**
   * @return query langauge expression as a string.
   */
  public function __toString() { 
    
    $str = '';
    
    try {
      $Database = AblePolecat_Server::getDatabase();
      $str = sprintf("%s %s %s", 
        $this->lvalue(), 
        $this->operator(), 
        $Database->quote($this->rvalue())
      );
    }
    catch (AblePolecat_Exception $Exception) {
      AblePolecat_Server::log(AblePolecat_LogInterface::WARNING, $Exception->getMessage());
    }
    return $str;
  }
}
