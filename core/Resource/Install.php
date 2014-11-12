<?php
/**
 * @file      polecat/core/Resource/Install.php
 * @brief     Starting point for interactive install procedure.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource', 'Restricted.php')));

class AblePolecat_Resource_Install extends AblePolecat_Resource_RestrictedAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  // private static $Resource;
  
  /**
   * Constants.
   */
  const UUID = '3f3630b0-3a9d-11e4-916c-0800200c9a66';
  const NAME = AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_Resource_Install
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Install($Subject);
      self::$Resource->setWakeupAccessRequest(AblePolecat_AccessControl_Constraint_Execute::getId());
    }
    return parent::wakeup($Subject);
    /*
    if (!isset(self::$Resource)) {
      //
      // Create resource.
      //
      self::$Resource = new AblePolecat_Resource_Install($Subject);
      self::$Resource->Head = 'Able Polecat | Install';
      
      //
      // Check if Able Polecat database exists.
      //
      $activeCoreDatabase = AblePolecat_Mode_Server::getActiveCoreDatabaseName();
      if ($activeCoreDatabase) {
        $version = AblePolecat_Version::getVersion(TRUE, 'HTML');
        self::$Resource->Body = sprintf("<p>Able Polecat %s is installed. Active database is [%s].</p>",
          $version,
          $activeCoreDatabase
        );
      }
      else {
        throw new AblePolecat_AccessControl_Exception('Able Polecat is not installed. Server database is not active.');
        // self::$Resource->Body = "<p>Detailed installation instructions are at <a href=\"https://github.com/kkuhrman/AblePolecat/wiki/Getting-Started\" target=\"new\">
          // https://github.com/kkuhrman/AblePolecat/wiki/Getting-Started</a></p>";
      }
    }
    return self::$Resource;
    */
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * @see http://stackoverflow.com/questions/2583707/can-i-create-a-database-using-pdo-in-php
   */
  protected function createCoreDb() {
    $host="localhost"; 

    $root="root"; 
    $root_password="rootpass"; 

    $user='newuser';
    $pass='newpass';
    $db="newdb"; 

    try {
    $dbh = new PDO("mysql:host=$host", $root, $root_password);

    $dbh->exec("CREATE DATABASE `$db`;
            CREATE USER '$user'@'localhost' IDENTIFIED BY '$pass';
            GRANT ALL ON `$db`.* TO '$user'@'localhost';
            FLUSH PRIVILEGES;") 
    or die(print_r($dbh->errorInfo(), true));

    } catch (PDOException $e) {
    die("DB ERROR: ". $e->getMessage());
    }
  }
  
  /**
   * Validates request URI path to ensure resource request can be fulfilled.
   *
   * @throw AblePolecat_Exception If request URI path is not validated.
   */
  protected function validateRequestPath() {
    $request_path = AblePolecat_Host::getRequest()->getRequestPath(FALSE);
    if (!isset($request_path[0]) || ($request_path[0] != 'install') || (count($request_path) > 1)) {
      $request_path = AblePolecat_Host::getRequest()->getRequestPath();
      throw new AblePolecat_Resource_Exception($request_path . ' is not a valid request URI path for ' . __CLASS__ . '.');
    }
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->setId(self::UUID);
    $this->setId(self::NAME);
  }
}