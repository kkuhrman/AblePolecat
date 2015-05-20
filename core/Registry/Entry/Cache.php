<?php
/**
 * @file      polecat/core/Registry/Entry/Cache.php
 * @brief     Encapsulates record of a resource registered in [resource].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_CacheInterface extends AblePolecat_Registry_EntryInterface {  
  /**
   * @return string.
   */
  public function getResourceId();
  
  /**
   * @return string.
   */
  public function getStatusCode();
  
  /**
   * @return string.
   */
  public function getMimeType();
  
  /**
   * @return string.
   */
  public function getCacheData();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Cache extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_CacheInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_Cache();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * Create the registry entry object and populate with given DOMNode data.
   *
   * @param DOMNode $Node DOMNode encapsulating registry entry.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function import(DOMNode $Node) {
    //
    // @todo: import [cache] registry entry.
    //
  }
  
  /**
   * Create DOMNode and populate with registry entry data .
   *
   * @param DOMDocument $Document Registry entry will be exported to this DOM Document.
   * @param DOMElement $Parent Registry entry will be appended to this DOM Element.
   *
   * @return DOMElement Exported element or NULL.
   */
  public function export(DOMDocument $Document, DOMElement $Parent) {
    //
    // @todo: export [cache] registry entry.
    //
  }
  
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey) {
    
    $CacheRegistration = NULL;
    
    if (is_array($primaryKey) && (2 == count($primaryKey))) {
      //
      // Create registry object and initialize primary key.
      //
      $CacheRegistration = new AblePolecat_Registry_Entry_Cache();
      isset($primaryKey['resourceId']) ? $CacheRegistration->resourceId = $primaryKey['resourceId'] : $CacheRegistration->resourceId = $primaryKey[0];
      isset($primaryKey['statusCode']) ? $CacheRegistration->statusCode = $primaryKey['statusCode'] : $CacheRegistration->statusCode = $primaryKey[1];
      
      //
      // Generate and execute SELECT statement.
      //
      $sql = __SQL()->          
        select('resourceId', 'statusCode', 'mimeType', 'lastModifiedTime', 'cacheData')->
        from('cache')->
        where(sprintf("`resourceId` = '%s' AND `statusCode` = %d", $CacheRegistration->resourceId, $CacheRegistration->statusCode));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_User_System::wakeup(), $sql);
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          isset($registrationInfo[0]['mimeType']) ? $CacheRegistration->mimeType = $registrationInfo[0]['mimeType'] : NULL;
          isset($registrationInfo[0]['lastModifiedTime']) ? $CacheRegistration->lastModifiedTime = $registrationInfo[0]['lastModifiedTime'] : NULL;
          isset($registrationInfo[0]['cacheData']) ? $CacheRegistration->cacheData = $registrationInfo[0]['cacheData'] : NULL;
        }
      }
    }
    return $CacheRegistration;
  }
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames() {
    return array(0 => 'resourceId', 1 => 'statusCode');
  }
  
  /**
   * Update or insert registration record.
   *
   * If the encapsulated registration exists, based on id property, it will be updated
   * to reflect object state. Otherwise, a new registration record will be created.
   *
   * @return AblePolecat_Command_Result or NULL.
   */
  public function save() {
    $sql = __SQL()->          
      replace(
        'resourceId', 
        'statusCode', 
        'mimeType', 
        'lastModifiedTime', 
        'cacheData')->
      into('cache')->
      values(
        $this->getResourceId(), 
        $this->getStatusCode(), 
        $this->getMimeType(), 
        $this->getLastModifiedTime(), 
        $this->getCacheData()
      );
    return $this->executeDml($sql);
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_CacheInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getResourceId() {
    return $this->getPropertyValue('resourceId');
  }
  
  /**
   * @return string.
   */
  public function getStatusCode() {
    return $this->getPropertyValue('statusCode');
  }
  
  /**
   * @return string.
   */
  public function getMimeType() {
    return $this->getPropertyValue('mimeType');
  }
  
  /**
   * @return string.
   */
  public function getCacheData() {
    return $this->getPropertyValue('cacheData');
  }
      
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}