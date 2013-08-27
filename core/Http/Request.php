<?php
/**
 * @file: Request.php
 * Encapsulates an HTTP request.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Http.php');

class AblePolecat_Http_Request implements AblePolecat_Http {
  
  /**
   * Get value of given query string variable.
   *
   * @param string $var Name of requested query string variable.
   *
   * @return mixed Value of requested variable or NULL.
   */
  public static function getVariable($var) {
    $value = NULL;
    if (isset($var) && isset($_REQUEST[$var])) {
      $value = $_REQUEST[$var];
    }
    return $value;
  }
  
  /**
   * Output the entire message as text.
   */
  public function __toString() {
	//
	// @todo:
	//
	return '';
  }
}