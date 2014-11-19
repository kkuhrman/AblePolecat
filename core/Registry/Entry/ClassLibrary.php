<?php
/**
 * @file      polecat/core/Registry/Entry/ClassLibrary.php
 * @brief     Encapsulates record of a resource registered in [classlib].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ClassLibraryInterface extends AblePolecat_Registry_EntryInterface {  
  /**
   * @return string.
   */
  public function getClassLibraryName();
  
  /**
   * @return string.
   */
  public function getClassLibraryId();
  
  /**
   * @return string.
   */
  public function getClassLibraryType();
  
  /**
   * @return string.
   */
  public function getMajorRevisionNumber();
  
  /**
   * @return string.
   */
  public function getMinorRevisionNumber();
  
  /**
   * @return string.
   */
  public function getRevisionNumber();
  
  
  /**
   * @return string.
   */
  public function getClassLibraryDirectory();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_ClassLibrary extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ClassLibraryInterface {
  
  /**
   * @var Array File statistics from stat().
   */
  private $fileStat;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_ClassLibrary();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey) {
    //
    // @todo: complete SELECT * FROM [classlib]
    //
  }
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames() {
    return array(0 => 'classLibraryId');
  }
  
  /**
   * Update or insert registration record.
   *
   * If the encapsulated registration exists, based on id property, it will be updated
   * to reflect object state. Otherwise, a new registration record will be created.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function save() {
    //
    // @todo: complete REPLACE [classlib]
    //
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ClassLibraryInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getClassLibraryName() {
    return $this->getPropertyValue('classLibraryName');
  }
  
  /**
   * @return string.
   */
  public function getClassLibraryId() {
    return $this->getPropertyValue('classLibraryId');
  }
  
  /**
   * @return string.
   */
  public function getClassLibraryType() {
    return $this->getPropertyValue('classLibraryType');
  }
  
  /**
   * @return string.
   */
  public function getMajorRevisionNumber() {
    return $this->getPropertyValue('major');
  }
  
  /**
   * @return string.
   */
  public function getMinorRevisionNumber() {
    return $this->getPropertyValue('minor');
  }
  
  /**
   * @return string.
   */
  public function getRevisionNumber() {
    return $this->getPropertyValue('revision');
  }
  
  /**
   * @return string.
   */
  public function getClassLibraryDirectory() {
    return $this->getPropertyValue('classLibraryDirectory');
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