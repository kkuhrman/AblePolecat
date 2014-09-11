<?php
/**
 * @file      polecat/core/QueryLanguage/Expression/Binary/Sql.php
 * @brief     Interface for a SQL binary expression.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'QueryLanguage', 'Expression', 'Binary.php')));

interface AblePolecat_QueryLanguage_Expression_Binary_SqlInterface extends AblePolecat_QueryLanguage_Expression_BinaryInterface {
}

class AblePolecat_QueryLanguage_Expression_Binary_Sql extends AblePolecat_QueryLanguage_Expression_BinaryAbstract {
  
  /********************************************************************************
   * Implementation of AblePolecat_QueryLanguage_ExpressionInterface.
   ********************************************************************************/
   
  /**
   * @return query langauge expression as a string.
   */
  public function __toString() { 
    
    $str = '';
    
    try {
      $str = sprintf("%s %s %s", 
        $this->lvalue(), 
        $this->operator(), 
        AblePolecat_Sql::getLiteralExpression($this->rvalue())
      );
    }
    catch (AblePolecat_Exception $Exception) {
      AblePolecat_Command_Log::invoke($this, $Exception->getMessage(), AblePolecat_LogInterface::WARNING);
    }
    return $str;
  }
}
