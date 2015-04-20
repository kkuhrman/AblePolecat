<?php
/**
 * @file      AblePolecat/usr/share/test/Data.php
 * @brief     Unit tests for AblePolecat_Data.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'UnitTest.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data.php')));

class AblePolecatTest_Data extends AblePolecat_UnitTest implements AblePolecat_UnitTestInterface {
  /**
   * Run all the tests in the class.
   *
   * @throw AblePolecat_UnitTest_Exception if any test fails.
   */
  public static function runTests() {
    
    self::test_getDataTypeName();
    self::test_castPrimitiveType_pass();
  }
  
  public static function test_getDataTypeName() {
    
    try {
      if (self::testMethod(
        'AblePolecat_Data',
        'getDataTypeName',
        array(new AblePolecatTest_Data()),
        'AblePolecatTest_Data'
      )) {
        self::setTestResult(__METHOD__, TRUE);
      }
    }
    catch (AblePolecat_UnitTest_Exception $Exception) {
      self::setTestResult(__METHOD__, FALSE, $Exception);
    }
  }
  
  public static function test_castPrimitiveType_pass() {
    try {
      //
      // Since expected result is not scalar must use AblePolecat_UnitTest_Result.
      //
      $expectedResult = new AblePolecat_UnitTest_Result();
      $expectedResult->setDataTypeName('AblePolecat_Data_Primitive_Scalar_String');
      if (self::testMethod(
        'AblePolecat_Data',
        'castPrimitiveType',
        array('this is a string'),
        $expectedResult
      )) {
        self::setTestResult(__METHOD__, TRUE);
      }
    }
    catch (AblePolecat_UnitTest_Exception $Exception) {
      self::setTestResult(__METHOD__, FALSE, $Exception);
    }
  }
 }