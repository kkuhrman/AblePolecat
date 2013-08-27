<?php
/**
 * @file: Http.php
 * Base interface for all classes encapsulating HTTP messages (request/response).
 */

interface AblePolecat_Http {
  /**
   * Output the entire message as text.
   */
  public function __toString();
}