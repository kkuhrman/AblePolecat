<?php
/**
 * Statement.php
 * Data/object retrieval or manipulation language statement interface.
 */

interface AblePolecat_QueryLanguage_StatementInterface {
  
  /**
   * @return query langauge statement as a string.
   */
  public function __toString();
}

class AblePolecat_QueryLanguage_Exception extends AblePolecat_Exception {
}