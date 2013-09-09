<?php
/**
 * @file: QueryLanguage.php
 * Encapsulates metadata for server support of a data definition, manipulation or retrieval language.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

 /**
  * Primary function is to indicate whether driver support for given QL exists on server.
  */
interface AblePolecat_QueryLanguage {
  
  /**
   * @return TRUE if driver support for QL syntax is functional on server, otherwise FALSE.
   */
  public static function isSupported();
}

/**
 * Exceptions thrown by query language classes.
 */
class AblePolecat_Query_Exception extends AblePolecat_Exception {
}