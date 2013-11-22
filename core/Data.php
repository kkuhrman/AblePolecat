<?php
/**
 * @file: Data.php
 * Base class for both scalar and not scalar data types in Able Polecat.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

interface AblePolecat_DataInterface extends Serializable {
  
  /**
   * @return mixed Encapsulated (scalar or not scalar) data.
   */
  public function getData();
  
  /**
   * @return bool TRUE if data has NULL value, otherwise FALSE.
   */
  public function isNull();
  
  /**
   * Casts the given parameter into an instance of data class.
   *
   * @param mixed $data
   *
   * @return Concrete instance of AblePolecat_DataInterface
   * @throw AblePolecat_Data_Exception if type cast is invalid.
   */
  public static function typeCast($data);
}

abstract class AblePolecat_DataAbstract implements AblePolecat_DataInterface {
  
  /**
   * @var mixed The value of the encapsulated data.
   */
  private $mData;
  
  protected function setData($data) {
    $this->mData = $data;
  }
  
  /**
   * @return mixed Encapsulated (scalar or not scalar) data.
   */
  public function getData() {
    return $this->mData;
  }
  
  /**
   * @return bool TRUE if data has NULL value, otherwise FALSE.
   */
  public function isNull() {
    return isset($this->mData);
  }
  
  final protected function __construct() {
    $args = func_get_args();
    if (isset($args[0])) {
      $this->mData = $args[0];
    }
    else {
      $this->mData = NULL;
    }
  }
}

/**
  * Exceptions thrown by Able Polecat data sub-classes.
  */
class AblePolecat_Data_Exception extends AblePolecat_Exception {
}