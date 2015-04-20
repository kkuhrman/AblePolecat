<?php
/**
 * @file      AblePolecat/core/UnitTest/Result.php
 * @brief     Encapsulates the results expected on success of a unit test.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'UnitTest.php')));

interface AblePolecat_UnitTest_ResultInterface {
  /**
   * Returns name of expected result data type.
   *
   * Should be the same as passing return value to AblePolecat_Data::getDataTypeName().
   * Either the name of a primitive data type or a class name.
   *
   * @return string.
   */
  public function getDataTypeName();
  
  /**
   * Set name of expected result data type.
   *
   * @param string $returnDataType.
   */
  public function setDataTypeName($returnDataType);
}

class AblePolecat_UnitTest_Result implements AblePolecat_UnitTest_ResultInterface {
  
  /**
   * @var string.
   */
  private $returnDataType;
  
  /**
   * Returns name of expected result data type.
   *
   * Should be the same as passing return value to AblePolecat_Data::getDataTypeName().
   * Either the name of a primitive data type or a class name.
   *
   * @return string.
   */
  public function getDataTypeName() {
    return $this->returnDataType;
  }
  
  /**
   * Set name of expected result data type.
   *
   * @param string $returnDataType.
   */
  public function setDataTypeName($returnDataType) {
    $this->returnDataType = $returnDataType;
  }
  
  public function __construct() {
    $this->returnDataType = 'null';
  }
}