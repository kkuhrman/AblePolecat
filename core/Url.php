<?php
/**
 * @file: Url.php
 * Encapsulates system (aka 'base') URL and URL related functions.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Server', 'Paths.php')));

class AblePolecat_Url {
  
  /**
   * @var string 'base' URL.
   */
  private static $base = NULL;
  
  /**
   * @return string base URL.
   */
  public function getBase() {
    if (!isset(self::$base)) {
      self::$base = '/';
      $protocol = 'http';
      if (isset($_SERVER['SERVER_PROTOCOL'])) {
        $pos = strrpos($_SERVER['SERVER_PROTOCOL'], '/');
        $protocol = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0 , $pos));
      }
      if (isset($_SERVER['SCRIPT_FILENAME']) && isset($_SERVER['REQUEST_URI']) && isset($_SERVER['HTTP_HOST'])) {
        //
        // $_SERVER uses '/', Windows uses '\', you say toe-mah-toe etc.
        //
        $polecat_root = str_replace(DIRECTORY_SEPARATOR, '/', ABLE_POLECAT_ROOT);
        
        //
        // relative path off Able Polecat root directory
        //
        $relative_filename = str_replace($polecat_root, '', $_SERVER['SCRIPT_FILENAME']);
        
        //
        // Able Polecat root directory should be virtual host root.
        // Trim any leading and trailing slashes from relative URL.
        //
        $request_uri = str_replace($relative_filename, '', $_SERVER['REQUEST_URI']);
        $pos = strpos($request_uri, '?');
        $pos ? $request_uri = substr($request_uri, 0, $pos) : NULL;
        $relative_base = trim($request_uri, '/');
        self::$base = sprintf("%s://%s/%s/", 
          $protocol,
          $_SERVER['HTTP_HOST'],
          $relative_base
        );
      }
    }
    return self::$base;
  }
  
  final protected function __construct() {
  }
}

/**
 * Exceptions thrown by Able Polecat URL functions.
 */
class AblePolecat_Url_Exception extends AblePolecat_Exception {
}