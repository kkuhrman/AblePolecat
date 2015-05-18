<?php
/**
 * @file      AblePolecat/core/UnitTest.php
 * @brief     Encapsulates a unit test.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'UnitTest', 'Result.php')));

interface AblePolecat_UnitTestInterface {
  
  /**
   * @return Array Results of test(s).
   */
  public static function getTestResults();
  
  /**
   * Run all the tests in the class.
   *
   * @throw AblePolecat_UnitTest_Exception if any test fails.
   */
  public static function runTests();
  
  /**
   * Set result of given test.
   *
   * @param string $methodName Name of test method (best practice is to use __METHOD__ or __FUNCTION__).
   * @param boolean $result TRUE | FALSE (PASS | FAIL).
   * @param AblePolecat_UnitTest_Exception $Exception thrown by test method.
   */
  public static function setTestResult($methodName, $result, AblePolecat_UnitTest_Exception $Exception = NULL);
  
  /**
   * Run test on given class method and return result.
   *
   * For tests on non-static methods, the first parameter should be an instance 
   * of the class being tested. The name of the class will suffice for tests of 
   * static methods.
   *
   * @param mixed $TestSubject Instance or name of class subjected to test.
   * @param string $methodName Name of method to test.
   * @param Array  $args Arguments passed to method.
   * @param mixed  $expectedReturnValue Result expected on success.
   *
   * @throw AblePolecat_UnitTest_Exception if any test fails.
   */
  public static function testMethod($TestSubject, $methodName, $args, $expectedReturnValue);
}

class AblePolecat_UnitTest {
  /**
   * @var Array Results of test(s).
   */
  private static $testResults = NULL;
  
  /********************************************************************************
   * Implementation of AblePolecat_UnitTestInterface
   ********************************************************************************/
   
  /**
   * @return Array Results of test(s).
   */
  public static function getTestResults() {
    if (!isset(self::$testResults)) {
      self::$testResults = array();
    }
    return self::$testResults;
  }
  
  /**
   * Set result of given test.
   *
   * @param string $methodName Name of test method (best practice is to use __METHOD__ or __FUNCTION__).
   * @param boolean $result TRUE | FALSE (PASS | FAIL).
   * @param AblePolecat_UnitTest_Exception $Exception thrown by test method.
   */
  public static function setTestResult($methodName, $result, AblePolecat_UnitTest_Exception $Exception = NULL) {
    if (!isset(self::$testResults)) {
      self::$testResults = array();
    }
    
    //
    // Create the test result object.
    //
    $testResult = new AblePolecat_UnitTest_Result();
    $methodParts = explode('::', $methodName);
    if (count($methodParts) == 2) {
      $testResult->setClassName($methodParts[0]);
      $testResult->setFunctionName($methodParts[1]);
    }
    else if (count($methodParts) == 1) {
      $testResult->setFunctionName($methodParts[0]);
    }
    switch ($result) {
      default:
        break;
      case TRUE:
        $testResult->setPass();
        break;
      case FALSE:
        $testResult->setFail($Exception);
        break;
    }
    // AblePolecat_Debug::kill($testResult);
    self::$testResults[] = $testResult;
  }
  
  /**
   * Run test on given class method and return result.
   *
   * For tests on non-static methods, the first parameter should be an instance 
   * of the class being tested. The name of the class will suffice for tests of 
   * static methods.
   *
   * @param mixed $TestSubject Instance or name of class subjected to test.
   * @param string $methodName Name of method to test.
   * @param Array  $args Arguments passed to method.
   * @param mixed  $expectedReturnValue Result expected on success.
   *
   * @throw AblePolecat_UnitTest_Exception if any test fails.
   */
  public static function testMethod($TestSubject, $methodName, $args, $expectedReturnValue) {
    
    $success = TRUE;
    
    //
    // Check input and set up test.
    //
    $className = AblePolecat_Data::getDataTypeName($TestSubject);
    if (!is_object($TestSubject)) {
      $className = $TestSubject;
    }
    if (!method_exists($TestSubject, $methodName)) {
      throw new AblePolecat_UnitTest_Exception("Method $className::$methodName does not exist.");
    }
    if (!is_array($args)) {
      throw new AblePolecat_UnitTest_Exception(sprintf("%s parameter three must be array. %s given.",
        __METHOD__,
        AblePolecat_Data::getDataTypeName($args)
      ));
    }
    $callable = array($TestSubject, $methodName);
    
    //
    // Run unit test.
    //
    $returnValue = 'null';
    try {
      $returnValue = call_user_func_array($callable, $args);
    }
    catch (Exception $Exception) {
      $returnValue = $Exception;
    }
    if (is_a($expectedReturnValue, 'AblePolecat_UnitTest_Result')) {
      $reportExpectedValue = $expectedReturnValue->getDataTypeName();
      $reportReturnValue = AblePolecat_Data::getDataTypeName($returnValue);
      if ($reportExpectedValue != $reportReturnValue) {
        throw self::createMismatchException(
          $className,
          $methodName,
          $reportExpectedValue,
          $reportReturnValue
        );
      }
      //
      // @todo: further evaluation of $expectedReturnValue against actual result.
      //
    }
    else {
      if ($returnValue !== $expectedReturnValue) {
        is_scalar($returnValue) ? $reportReturnValue = strval($returnValue) : $reportReturnValue = AblePolecat_Data::getDataTypeName($returnValue);
        is_scalar($expectedReturnValue) ? $reportExpectedValue = strval($expectedReturnValue) : $reportExpectedValue = AblePolecat_Data::getDataTypeName($expectedReturnValue);
        throw self::createMismatchException(
          $className,
          $methodName,
          $reportExpectedValue,
          $reportReturnValue
        );
      }
    }
    
    return $success;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Create an exception based on expected results not matching actual results.
   *
   * This function does not throw an exception, rather returns the exception 
   * object, ready to throw or pass to a utility function like @see setTestResult().
   *
   * @param string $className,
   * @param string $methodName
   * @param mixed $reportExpectedValue
   * @param mixed $reportReturnValue
   *
   * @return AblePolecat_UnitTest_Exception.
   */
  public static function createMismatchException(
    $className,
    $methodName,
    $reportExpectedValue,
    $reportReturnValue
  ) {
    return new AblePolecat_UnitTest_Exception(sprintf("Unit test of %s::%s failed. Expected %s, returned %s.",
      $className,
      $methodName,
      $reportExpectedValue,
      $reportReturnValue
    ));
  }
}