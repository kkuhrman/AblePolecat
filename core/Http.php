<?php
/**
 * @file: Http.php
 * Base interface for all classes encapsulating HTTP activity.
 */

interface AblePolecat_HttpInterface {
  /**
   * At present, should return HTTP/1.1.
   */
  public function getVersion();
}