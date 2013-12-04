<?php
/**
 * @file: Sql.php
 * Interface for a binary SQL expression.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'QueryLanguage', 'Expression', 'Binary.php')));

interface AblePolecat_QueryLanguage_Expression_Binary_SqlInterface extends AblePolecat_QueryLanguage_Expression_BinaryInterface {
}

class AblePolecat_QueryLanguage_Expression_Binary_Sql extends AblePolecat_QueryLanguage_Expression_BinaryAbstract {
  
  /**
   * @return query langauge expression as a string.
   */
  public function __toString() { 
    $Database = AblePolecat_Server::getDatabase("polecat");
    return sprintf("%s %s %s", 
      $this->lvalue(), 
      $this->operator(), 
      $Database->quote($this->rvalue())
    );
  }
}
