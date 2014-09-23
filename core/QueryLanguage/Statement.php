<?php
/**
 * @file      polecat/core/QueryLanguage/Statement.php
 * @brief     Data/object retrieval or manipulation language statement interface.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'QueryLanguage.php')));

interface AblePolecat_QueryLanguage_StatementInterface {
  
  /**
   * @return query langauge statement as a string.
   */
  public function __toString();
}