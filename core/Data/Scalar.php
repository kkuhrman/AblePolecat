<?php
/**
 * @file: Scalar.php
 * Encapsulates a scalar variable.
 */

require_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Data.php');

abstract class AblePolecat_Data_Scalar extends AblePolecat_DataAbstract {
  
  /**
   * @return string serialized representation of AblePolecat_Data_Scalar.
   */
  public function serialize() {
    return serialize($this->getData());
  }
  
  /**
   * @return concrete instance of AblePolecat_Data_Scalar.
   */
  public function unserialize($data) {
    $this->setData(unserialize($data));
  }
}