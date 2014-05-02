<?php
/**
 * @file: polecat/core/AccessControl/Resource/Locater/Dsn.php
 * Encapsulates a resource locater for DSN.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource', 'Locater.php')));

interface AblePolecat_AccessControl_Resource_Locater_DsnInterface extends AblePolecat_AccessControl_Resource_LocaterInterface {
  
  /**
   * @return DOMString DSN.
   */
  public function getDsn();
}

class AblePolecat_AccessControl_Resource_Locater_Dsn extends AblePolecat_AccessControl_Resource_Locater implements AblePolecat_AccessControl_Resource_Locater_DsnInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Resource_Locater_DsnInterface.
   ********************************************************************************/
  
  /**
   * @return DOMString DSN.
   */
  public function getDsn() {
    
    //
    // @todo str_replace is a hack for cleansing/normalizing db name
    //
    $dsn = sprintf("%s:dbname=%s;host=%s",
      $this->getProtocol(),
      str_replace('/', '', $this->getPathname()),
      $this->getHost());
    $port = $this->getPort();
    isset($port) ? $dsn .= ";port=$port" : NULL;
    return $dsn;
  }
   
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Resource_LocaterInterface.
   ********************************************************************************/
  
  /**
   * Create URL.
   * 
   * @param DOMString $url Relative or absolute path.
   * @param optional DOMString $baseURL.
   *
   * @return object Instance of class implementing AblePolecat_AccessControl_Resource_LocaterInterface or NULL.
   */
  public static function create($url, $baseURL = NULL) {
    isset($baseURL) ? $url = $baseURL . self::URI_SLASH . $url : NULL;
    $Locater = new AblePolecat_AccessControl_Resource_Locater_Dsn($url);
    return $Locater;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct();
   */
  protected function initialize() {
    
    //
    // Check if locater is DSN syntax mysql:dbname=database;host=localhost;port=#### or
    // standard URL syntax mysql://user:password@localhost/database.
    //
    $matches = array();
    preg_match_all("(dbname=[0-9a-zA-Z]|host=[0-9a-zA-Z]|port=[0-9])", $this->getRawUrl(), $matches);
    
    if (isset($matches[0]) && count($matches[0])) {
      //
      // Process DSN syntax
      //
      $pos = strpos($this->getRawUrl(), ':');
      $this->setProtocol(trim(substr($this->getRawUrl(), 0, $pos)));
      $urlparts = explode(';', trim(substr($this->getRawUrl(), 1 + $pos)));
      foreach($urlparts as $key => $part) {
        $pos = strpos($part, '=');
        $parameter = trim(substr($part, 0, $pos));
        $value = trim(substr($part, 1 + $pos));
        switch($parameter) {
          default:
            //
            // unexpected parameter
            //
            break;
          case 'dbname':
            $this->setPathname($value);
            break;
          case 'host':
            $this->setHost($value);
            break;
          case 'port':
            $this->setPort($value);
            break;
        }
      }
    }
    else {
      //
      // Process standard URL syntax
      //
      parent::initialize();
    }
  }
}