<?php
/**
 * @file      polecat/core/Registry/Resource.php
 * @brief     Manages registry application (web) resources.
 *
 * Able Polecat expects the part of the URI, which follows the host or virtual host
 * name to define a 'resource' on the system. This function returns the data (model)
 * corresponding to request. If no corresponding resource is located on the system, 
 * or if an application error is encountered along the way, Able Polecat has a few 
 * built-in resources to deal with these situations.
 *
 * NOTE: Although a 'resource' may comprise more than one path component (e.g. 
 * ./books/[ISBN] or ./products/[SKU] etc), an Able Polecat resource is identified by
 * the first part only (e.g. 'books' or 'products') combined with a UUID. Additional
 * path parts are passed to the top-level resource for further resolution. This is 
 * why resource classes validate the URI, to ensure it follows expectations for syntax
 * and that request for resource can be fulfilled. In short, the Able Polecat server
 * really only fulfils the first part of the resource request and delegates the rest to
 * the 'resource' itself.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Resource.php')));

class AblePolecat_Registry_Resource extends AblePolecat_RegistryAbstract {
  
  /**
   * AblePolecat_AccessControl_Article_StaticInterface
   */
  const UUID = '98d3068a-b7b2-11e4-a12d-0050569e00a2';
  const NAME = __CLASS__;
  
  /**
   * @var AblePolecat_Registry_Resource Singleton instance.
   */
  private static $Registry = NULL;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
   
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Registry_Resource Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Registry)) {
      try {
        self::$Registry = new AblePolecat_Registry_Resource($Subject);
      }
      catch (Exception $Exception) {
        self::$Registry = NULL;
        throw new AblePolecat_Registry_Exception($Exception->getMessage(), AblePolecat_Error::WAKEUP_FAIL);
      }
    }
    return self::$Registry;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Database_InstallerInterface.
   ********************************************************************************/
   
  /**
   * Install class registry on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public static function install(AblePolecat_DatabaseInterface $Database) {
  }
  
  /**
   * Update current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if update fails.
   */
  public static function update(AblePolecat_DatabaseInterface $Database) {
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Return registration entry for core resource corresponding to HTTP request.
   *
   * @param AblePolecat_Message_RequestInterface $Request
   * 
   * @return AblePolecat_Registry_Entry_Resource
   */
  public static function getCoreResourceRegistration(AblePolecat_Message_RequestInterface $Request) {
    
    $ResourceRegistration = AblePolecat_Registry_Entry_Resource::create();
    $resourceName  = NULL;
    
    if (AblePolecat_Database_Pdo::ready()) {
      //
      // Resource is not registered. Use a system resource.      
      // Assign resource id and class name.
      // Extract the part of the URI, which defines the resource.
      //
      $requestPathInfo = $Request->getRequestPathInfo();
      if (isset($requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME])) {
        $resourceName = $requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME];
      }
      switch ($resourceName) {
        default:
          //
          // Request did not resolve to a registered or system resource class.
          // Log status and return error resource.
          //
          $message = sprintf("Request did not resolve to a registered resource (resource=%s; path=%s; host=%s).",
            $resourceName, 
            $Request->getRequestPath(),
            $Request->getHostName()
          );
          AblePolecat_Command_Log::invoke($this->getDefaultCommandInvoker(), $message, AblePolecat_LogInterface::STATUS);
          $ResourceRegistration->id = AblePolecat_Resource_Core_Error::UUID;
          $ResourceRegistration->classId = AblePolecat_Resource_Core_Error::UUID;
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_ACK:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME:
          $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME;
          $ResourceRegistration->id = AblePolecat_Resource_Core_Ack::UUID;
          $ResourceRegistration->classId = AblePolecat_Resource_Core_Ack::UUID;
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_UTIL:
          $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_UTIL;
          $ResourceRegistration->id = AblePolecat_Resource_Restricted_Util::UUID;
          $ResourceRegistration->classId = AblePolecat_Resource_Restricted_Util::UUID;
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL:
          $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL;
          $ResourceRegistration->id = AblePolecat_Resource_Restricted_Install::UUID;
          $ResourceRegistration->classId = AblePolecat_Resource_Restricted_Install::UUID;
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_UPDATE:
          $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_UPDATE;
          $ResourceRegistration->id = AblePolecat_Resource_Restricted_Update::UUID;
          $ResourceRegistration->classId = AblePolecat_Resource_Restricted_Update::UUID;
          break;
      }
    }
    else {
      //
      // There is no active database connection, redirect to install resource.
      //
      $ResourceRegistration->name = AblePolecat_Message_RequestInterface::RESOURCE_NAME_INSTALL;
      $ResourceRegistration->id = AblePolecat_Resource_Restricted_Install::UUID;
      $ResourceRegistration->classId = AblePolecat_Resource_Restricted_Install::UUID;
    }
    return $ResourceRegistration;
  }
    
  /**
   * Return registration data on resource corresponding to request URI/path.
   *
   * @see AblePolecat_ResourceAbstract::validateRequestPath()
   *
   * @param AblePolecat_Message_RequestInterface $Request
   * 
   * @return AblePolecat_Registry_Entry_Resource
   */
  public static function getRegisteredResource(AblePolecat_Message_RequestInterface $Request) {
    
    $ResourceRegistration = NULL;
    $resourceName  = NULL;
    
    if (AblePolecat_Database_Pdo::ready()) {
      //
      // Get project database.
      //
      $CoreDatabase = AblePolecat_Database_Pdo::wakeup($Subject);
      
      //
      // Look up resource registration in [resource]
      // Extract the part of the URI, which defines the resource.
      //
      $requestPathInfo = $Request->getRequestPathInfo();
      if (isset($requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME])) {
        $resourceName = $requestPathInfo[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME];
      }
      $sql = __SQL()->          
        select(
          'id', 
          'name', 
          'hostName', 
          'classId', 
          'lastModifiedTime')->
        from('resource')->
        where(sprintf("`name` = '%s' AND `hostName` = '%s'", $resourceName, $Request->getHostName()));
      $QueryResult = $CoreDatabase->query($sql);
      if (isset($QueryResult[0])) {
        $ResourceRegistration = AblePolecat_Registry_Entry_Resource::create();
        isset($QueryResult[0]['id']) ? $ResourceRegistration->id = $QueryResult[0]['id'] : NULL;
        isset($QueryResult[0]['name']) ? $ResourceRegistration->name = $QueryResult[0]['name'] : NULL;
        isset($QueryResult[0]['hostName']) ? $ResourceRegistration->hostName = $QueryResult[0]['hostName'] : NULL;
        isset($QueryResult[0]['classId']) ? $ResourceRegistration->classId = $QueryResult[0]['classId'] : NULL;
        isset($QueryResult[0]['lastModifiedTime']) ? $ResourceRegistration->lastModifiedTime = $QueryResult[0]['lastModifiedTime'] : NULL;
        
        //
        // Update cache entry if resource class file has been modified since last resource registry entry update.
        //
        if (isset($ResourceRegistration->classId)) {
          $ClassRegistration = AblePolecat_Registry_Entry_Class::fetch($ResourceRegistration->classId);
          if ($ClassRegistration && isset($ClassRegistration->lastModifiedTime)) {
            if ($ClassRegistration->lastModifiedTime > $ResourceRegistration->lastModifiedTime) {
              $sql = __SQL()->          
                update('resource')->
                set('lastModifiedTime')->
                values($ClassRegistration->lastModifiedTime)->
                where(sprintf("id = '%s'", $ResourceRegistration->id));
              $CoreDatabase->execute($sql);
              $ResourceRegistration->lastModifiedTime = $ClassRegistration->lastModifiedTime;
            }
          }
        }
      }
    }
    if (!isset($ResourceRegistration)) {
      $ResourceRegistration = self::getCoreResourceRegistration($Request);
    }
    return $ResourceRegistration;
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    parent::initialize();
  }
}