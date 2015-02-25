<?php
/**
 * @file      polecat/core/Registry/Response.php
 * @brief     Manages registry of HTTP response classes.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'DomNode', 'Response.php')));

class AblePolecat_Registry_Response extends AblePolecat_RegistryAbstract {
  
  /**
   * AblePolecat_AccessControl_Article_StaticInterface
   */
  const UUID = 'b88f13c7-b7b2-11e4-a12d-0050569e00a2';
  const NAME = __CLASS__;
  
  /**
   * @var AblePolecat_Registry_Response Singleton instance.
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
   * @return AblePolecat_Registry_Response Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Registry)) {
      self::$Registry = new AblePolecat_Registry_Response($Subject);
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
   * Return registration data for core response.
   *
   * @param string $resourceId
   * @param int $statusCode
   * 
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function getCoreResponseRegistration($resourceId, $statusCode) {
    //
    // No response registration record; use one of the core response classes.
    //
    $ResponseRegistration = AblePolecat_Registry_Entry_DomNode_Response::create();
    $ResponseRegistration->resourceId = $resourceId; 
    $ResponseRegistration->statusCode = $statusCode;
    
    switch ($resourceId) {
      default:
        $ResponseRegistration->id = AblePolecat_Message_Response_Xml::UUID;
        $ResponseRegistration->name = 'AblePolecat_Message_Response_Xml';
        $ResponseRegistration->classId = AblePolecat_Message_Response_Xml::UUID;
        break;
      case AblePolecat_Resource_Core_Form::UUID:
      case AblePolecat_Resource_Restricted_Install::UUID:
      case AblePolecat_Resource_Restricted_Update::UUID:
      case AblePolecat_Resource_Restricted_Util::UUID:
        $ResponseRegistration->id = AblePolecat_Message_Response_Xhtml::UUID;
        $ResponseRegistration->name = 'AblePolecat_Message_Response_Xhtml';
        $ResponseRegistration->classId = AblePolecat_Message_Response_Xhtml::UUID;
        break;
    }
    return $ResponseRegistration;
  }
  
  /**
   * Return registration data for response to given resource and status code.
   *
   * @param string $resourceId
   * @param int $statusCode
   * 
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function getRegisteredResponse($resourceId, $statusCode) {
    
    $ResponseRegistration = NULL;
    
    if (AblePolecat_Database_Pdo::ready()) {
      //
      // Get project database.
      //
      $CoreDatabase = AblePolecat_Database_Pdo::wakeup();
      
      $sql = __SQL()->
        select(
          'id', 
          'name', 
          'resourceId', 
          'statusCode', 
          'classId', 
          'lastModifiedTime')->
        from('response')->
        where(sprintf("`resourceId` = '%s' AND `statusCode` = %d", $resourceId, $statusCode));
      $QueryResult = $CoreDatabase->query($sql);
      if (isset($QueryResult[0])) {
        $ResponseRegistration = AblePolecat_Registry_Entry_DomNode_Response::create();
        isset($QueryResult[0]['id']) ? $ResponseRegistration->id = $QueryResult[0]['id'] : NULL;
        isset($QueryResult[0]['name']) ? $ResponseRegistration->name = $QueryResult[0]['name'] : NULL;
        isset($QueryResult[0]['resourceId']) ? $ResponseRegistration->resourceId = $QueryResult[0]['resourceId'] : NULL;
        isset($QueryResult[0]['statusCode']) ? $ResponseRegistration->statusCode = $QueryResult[0]['statusCode'] : NULL;
        isset($QueryResult[0]['classId']) ? $ResponseRegistration->classId = $QueryResult[0]['classId'] : NULL;
        isset($QueryResult[0]['lastModifiedTime']) ? $ResponseRegistration->lastModifiedTime = $QueryResult[0]['lastModifiedTime'] : NULL;
        
        //
        // Update cache entry if response class and corresponding template files have been modified since last 
        // response registry entry update.
        //
        $ClassRegistration = AblePolecat_Registry_Entry_Class::fetch($ResponseRegistration->classId);
        
        //
        // @todo: get template for given article id etc.
        //
        $TemplateRegistration = AblePolecat_Registry_Entry_Template::create();
        
        //
        // Check if resource and/or response have been modified since last cache entry.
        //
        $lastModifiedTimes = array(
          $ResponseRegistration->getLastModifiedTime(),
          $ClassRegistration->getLastModifiedTime(),
          $TemplateRegistration->getLastModifiedTime()
        );
        $mostRecentModifiedTime = AblePolecat_Data_Primitive_Scalar_Integer::max($lastModifiedTimes);
        
        if ($mostRecentModifiedTime != $CacheRegistration->getLastModifiedTime()) {
          $sql = __SQL()->          
            update('response')->
            set('lastModifiedTime')->
            values($mostRecentModifiedTime)->
            where(sprintf("`resourceId` = '%s' AND `statusCode` = %d", $resourceId, $statusCode));
          $CoreDatabase->execute($sql);
          $ResponseRegistration->lastModifiedTime = $mostRecentModifiedTime;
        }
      }
    }
    if (!isset($ResponseRegistration)) {
      $ResponseRegistration = self::getCoreResponseRegistration($resourceId, $statusCode);
    }
    return $ResponseRegistration;
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    parent::initialize();
  }
}