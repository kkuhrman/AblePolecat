<?php
/**
 * @file      polecat/core/Registry/Entry/Class.php
 * @brief     Encapsulates record of a resource registered in [class].
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry.php')));

interface AblePolecat_Registry_Entry_ClassInterface extends AblePolecat_Registry_EntryInterface {  
  /**
   * @return string.
   */
  public function getClassName();
  
  /**
   * @return string.
   */
  public function getClassId();
  
  /**
   * @return string.
   */
  public function getClassLibraryId();
  
  /**
   * @return string.
   */
  public function getClassScope();
  
  /**
   * @return bool.
   */
  public function getIsRequired();
  
  /**
   * @return string.
   */
  public function getClassFullPath();
  
  /**
   * @return string.
   */
  public function getClassFactoryMethod();
  
  /**
   * @return int Time of last modification to class file (Unix timestamp).
   */
  public function getClassLastModifiedTime();
}

/**
 * Standard argument list.
 */
class AblePolecat_Registry_Entry_Class extends AblePolecat_Registry_EntryAbstract implements AblePolecat_Registry_Entry_ClassInterface {
  
  /**
   * @var Array File statistics from stat().
   */
  private $fileStat;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * PHP magic method is run when writing data to inaccessible properties.
   *
   * @param string $name  Name of property to set.
   * @param mixed  $value Value to assign to given property.
   */
  public function __set($name, $value) {
    
    if ($name == 'classFullPath') {
      $this->fileStat = stat($value);
      if ($this->fileStat && isset($this->fileStat['mtime'])) {
        parent::__set('classLastModifiedTime', $this->fileStat['mtime']);
      }
      else {
        throw new AblePolecat_Registry_Exception("Failed to retrieve file stats on $value.");
      }
    }
    else if ($name == 'classLastModifiedTime') {
      trigger_error("$name is read-only property.");
    }
    parent::__set($name, $value);
  }
  
  /**
   * Creational method.
   *
   * @return Concrete instance of class implementing AblePolecat_InProcObjectInterface.
   */
  public static function create() {
    return new AblePolecat_Registry_Entry_Class();
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_Entry_ClassInterface.
   ********************************************************************************/
    
  /**
   * @return string.
   */
  public function getClassName() {
    return $this->getPropertyValue('className');
  }
  
  /**
   * @return string.
   */
  public function getClassId() {
    return $this->getPropertyValue('classId');
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
  public function getClassScope() {
    return $this->getPropertyValue('classScope');
  }
  
  /**
   * @return bool.
   */
  public function getIsRequired() {
    return $this->getPropertyValue('isRequired');
  }
  
  /**
   * @return string.
   */
  public function getClassFullPath() {
    return $this->getPropertyValue('classFullPath');
  }
  
  /**
   * @return string.
   */
  public function getClassFactoryMethod() {
    return $this->getPropertyValue('classFactoryMethod');
  }
  
  /**
   * @return int Time of last modification to class file (Unix timestamp).
   */
  public function getClassLastModifiedTime() {
    return $this->getPropertyValue('classLastModifiedTime');
  }
      
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Output class state to debug log.
   */
  public function dumpState() {
    $message = sprintf("REGISTRY: className=%s, classId=%s; classLibraryId=%s; classScope=%s; classFullPath=%s, classFactoryMethod=%s, classLastModifiedTime=%d",
      $this->getClassName(),
      $this->getClassId(),
      $this->getClassLibraryId(),
      $this->getClassScope(),
      $this->getClassFullPath(),
      $this->getClassFactoryMethod(),
      $this->getClassLastModifiedTime()
    );
    AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    parent::initialize();
  }
}