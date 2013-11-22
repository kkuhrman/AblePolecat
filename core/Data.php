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
}

/**
  * Exceptions thrown by Able Polecat data sub-classes.
  */
class AblePolecat_Data_Exception extends AblePolecat_Exception {
}