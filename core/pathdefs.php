<?php
/**
 * @file: pathdefs.php
 * Define and verify required file system paths for Able Polecat to boot.
 */

//
// Root directory.
//
if (!defined('ABLE_POLECAT_ROOT')) {
  $ABLE_POLECAT_ROOT = dirname(__DIR__);
  define('ABLE_POLECAT_ROOT', $ABLE_POLECAT_ROOT);
}

//
// Base URL
//
if (!defined('ABLE_POLECAT_BASE_URL')) {
  $ABLE_POLECAT_BASE_URL = '/';
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
    $ABLE_POLECAT_BASE_URL = sprintf("%s://%s/%s/", 
      $protocol,
      $_SERVER['HTTP_HOST'],
      $relative_base
    );
  }
  
  // if (isset($_SERVER['HTTP_HOST'])) {
    // $ABLE_POLECAT_BASE_URL = 'http://' . $_SERVER['HTTP_HOST'];
    // if (isset($_SERVER['REQUEST_URI'])) {
      // $pos = strrpos($_SERVER['REQUEST_URI'], '/');
      // $req = substr($_SERVER['REQUEST_URI'], 0 , $pos);
      // $ABLE_POLECAT_BASE_URL .= $req;
    // }
  // }
  
  define('ABLE_POLECAT_BASE_URL', $ABLE_POLECAT_BASE_URL);
}

//
// Path to Able Polecat library.
//
if (!defined('ABLE_POLECAT_PATH')) {
  $ABLE_POLECAT_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'libraries'  . DIRECTORY_SEPARATOR . 'Able Polecat';
  define('ABLE_POLECAT_PATH', $ABLE_POLECAT_PATH);
}

//
// Path to Able Polecat configuration files.
//
if (!defined('ABLE_POLECAT_CONF_PATH')) {
  $ABLE_POLECAT_CONF_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'conf';
  define('ABLE_POLECAT_CONF_PATH', $ABLE_POLECAT_CONF_PATH);
}

//
// Path to Able Polecat development tools.
//
if (!defined('ABLE_POLECAT_DEV_PATH')) {
  $ABLE_POLECAT_DEV_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'dev';
  define('ABLE_POLECAT_DEV_PATH', $ABLE_POLECAT_DEV_PATH);
}

//
// Path to Able Polecat modules.
//
if (!defined('ABLE_POLECAT_MODS_PATH')) {
  $ABLE_POLECAT_MODS_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'mods';
  define('ABLE_POLECAT_MODS_PATH', $ABLE_POLECAT_MODS_PATH);
}

//
// Path to Able Polecat unit test and qa directory.
//
if (!defined('ABLE_POLECAT_QA_PATH')) {
  $ABLE_POLECAT_QA_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'qa';
  define('ABLE_POLECAT_QA_PATH', $ABLE_POLECAT_QA_PATH);
}

//
// Path to Able Polecat user sites files.
//
if (!defined('ABLE_POLECAT_SITES_PATH')) {
  $ABLE_POLECAT_SITES_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'user'  . DIRECTORY_SEPARATOR . 'sites';
  define('ABLE_POLECAT_SITES_PATH', $ABLE_POLECAT_SITES_PATH);
}

//
// Path to Able Polecat user services files.
//
if (!defined('ABLE_POLECAT_SERVICES_PATH')) {
  $ABLE_POLECAT_SERVICES_PATH = ABLE_POLECAT_ROOT . DIRECTORY_SEPARATOR . 'user'  . DIRECTORY_SEPARATOR . 'services';
  define('ABLE_POLECAT_SERVICES_PATH', $ABLE_POLECAT_SERVICES_PATH);
}
