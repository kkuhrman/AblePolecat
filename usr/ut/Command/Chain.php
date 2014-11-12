<?php
/**
 * @file      polecat/usr/ut/Command/Chain.php
 * @brief     Unit tests for AblePolecat_Command_Chain.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Session.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Host.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Chain.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'UnitTest.php')));

class AblePolecat_Command_Chain_TestClass implements AblePolecat_UnitTestInterface {
  
  const TEST_SUBJECT = 'AblePolecat_Command_Chain';
  
  /**
   * Run all the tests in the class.
   *
   * @throw AblePolecat_UnitTest_Exception if any test fails.
   */
  public static function runTests() {
    self::testSetIdenticalTarget();
    self::testSetBadLink();
    self::testDispatch();
    self::testTargetList();
  }
  
  public static function testSetIdenticalTarget() {
    
    $pass = FALSE;
    
    try {
      $ServerMode = AblePolecat_Mode_Server::wakeup();
      $CommandChain = AblePolecat_Command_Chain::wakeup();
      $CommandChain->setCommandLink($ServerMode, $ServerMode);
      throw new AblePolecat_UnitTest_Exception(self::TEST_SUBJECT . ' unit tests failed on ' . __METHOD__);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      // AblePolecat_Debug::kill($Exception);
      $pass = TRUE;
    }
    return $pass;
  }
  
  public static function testSetBadLink() {
    
    $pass = FALSE;
    
    try {
      //
      // During initialization, AblePolecat_Host sets AblePolecat_Mode_Server as reverse link
      // and AblePolecat_Mode_User as forward link.
      //
      $Host = AblePolecat_Host::wakeup();
      $ServerMode = AblePolecat_Mode_Server::wakeup();
      $CommandChain = AblePolecat_Command_Chain::wakeup();
      $CommandChain->setCommandLink($ServerMode, $Host);
      throw new AblePolecat_UnitTest_Exception(self::TEST_SUBJECT . ' unit tests failed on ' . __METHOD__);
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $pass = TRUE;
    }
    return $pass;
  }
  
  public static function testDispatch() {
    $Version = AblePolecat_Version::getVersion(TRUE, 'text');
    $Agent = AblePolecat_AccessControl_Agent_System::wakeup();
    $CommandResult = AblePolecat_Command_Server_Version::invoke($Agent);
    if (!$CommandResult->success() || ($CommandResult->value() != $Version)) {
      throw new AblePolecat_UnitTest_Exception(self::TEST_SUBJECT . ' unit tests failed on ' . __METHOD__);
    }
  }
  
  public static function testTargetList() {
    $ServerMode = AblePolecat_Mode_Server::wakeup();
    $UserMode = AblePolecat_Mode_User::wakeup();
    $CommandChain = AblePolecat_Command_Chain::wakeup();
    $SuperiorTarget = $CommandChain->getBottomCommandTarget();
    $SubordinateTarget = $CommandChain->getTopCommandTarget();
    if (($ServerMode::getId() != $SuperiorTarget::getId()) || ($UserMode::getId() != $SubordinateTarget::getId())) {
      AblePolecat_Debug::kill($CommandChain);
      throw new AblePolecat_UnitTest_Exception('command link not properly established');
    }
    return TRUE;
  }
}