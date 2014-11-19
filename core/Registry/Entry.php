<?php
/**
 * @file      polecat/core/Registry/Entry.php
 * @brief     Encapsulates a record in one of the Able Polecat core registries.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'DynamicObject.php');

interface AblePolecat_Registry_EntryInterface extends AblePolecat_DynamicObjectInterface {
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey);
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames();
  
  /**
   * Update or insert registration record.
   *
   * If the encapsulated registration exists, based on id property, it will be updated
   * to reflect object state. Otherwise, a new registration record will be created.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function save();
  
  /**
   * @return int
   */
  public function getLastModifiedTime();
}

abstract class AblePolecat_Registry_EntryAbstract extends AblePolecat_DynamicObjectAbstract implements AblePolecat_Registry_EntryInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
   
  /**
   * @return int
   */
  public function getLastModifiedTime() {
    return $this->getPropertyValue('lastModifiedTime');
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
    $this->lastModifiedTime = 0;
  }
}