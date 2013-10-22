<?php
/**
 * QueryLanguage.php
 * Data/object retrieval or manipulation language interface.
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'Message', 'Request.php')));
include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_PATH, 'QueryLanguage', 'Statement.php')));

interface AblePolecat_QueryLanguageInterface {
  
  /**
   * Prepare request object from query language statement.
   *
   * @param string $query Query language statement.
   *
   * @return AblePolecat_Message_RequestInterface or NULL.
   * @throw AblePolecat_QueryLanguage_Exception if query is invalid.
   */
  public static function prepareRequest($query);
  
  /**
   * Prepare AblePolecat_QueryLanguage_StatementInterface object from AblePolecat_Message_RequestInterface object.
   *
   * @param AblePolecat_Message_RequestInterface $Request.
   *
   * @return AblePolecat_QueryLanguage_StatementInterface or NULL.
   * @throw AblePolecat_QueryLanguage_Exception if query is invalid.
   */
  public static function prepareStatement(AblePolecat_Message_RequestInterface $Request);
}

class AblePolecat_QueryLanguage_Exception extends AblePolecat_Exception {
}