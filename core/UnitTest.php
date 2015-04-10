<?php
/**
 * @file      polecat/core/UnitTest.php
 * @brief     Encapsulates a unit test.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'UnitTest.php')));

interface AblePolecat_UnitTestInterface {
  
  /**
   * Run all the tests in the class.
   *
   * @throw AblePolecat_UnitTest_Exception if any test fails.
   */
  public static function runTests();
}