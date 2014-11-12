<?php
/**
 * @file      polecat/usr/ut/Transaction/Get/Resource.php
 * @brief     Unit tests for AblePolecat_Transaction_Get_Resource.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Server.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Request', 'Get.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Registry', 'Entry', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Resource', 'Core.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Get', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'UnitTest.php')));

class AblePolecat_Transaction_Get_Resource_TestClass implements AblePolecat_UnitTestInterface {
  
  const TEST_SUBJECT = 'AblePolecat_Transaction_Get_Resource';
  
  /**
   * Run all the tests in the class.
   *
   * @throw AblePolecat_UnitTest_Exception if any test fails.
   */
  public static function runTests() {
    self::testCreateTransaction();
  }
  
  public static function testCreateTransaction() {
    
    $pass = TRUE;
    
    try {
      $className = 'AblePolecat_Transaction_Get_Resource';
      $classFactoryMethod = 'wakeup';
      // $ServerMode = AblePolecat_Mode_Server::wakeup();
      $Agent = AblePolecat_AccessControl_Agent_System::wakeup();
      $Request = AblePolecat_Message_Request_Get::create();
      
      $ResourceRegistration = AblePolecat_Registry_Entry_Resource::create();
      $ResourceRegistration->resourceId = AblePolecat_Resource_Ack::UUID;
      $ResourceRegistration->resourceClassName = 'AblePolecat_Resource_Ack';
      $ResourceRegistration->transactionClassName = NULL;
      $ResourceRegistration->authorityClassName = NULL;
      $ResourceRegistration->resourceDenyCode = 200;
      
      $parameters = array(
        $Agent,
        $Agent,
        $Request,
        $ResourceRegistration
      );
      
      $Instance = call_user_func_array(array($className, $classFactoryMethod), $parameters);
      // AblePolecat_Debug::kill($Instance);
      // $Transaction = AblePolecat_Transaction_Get_Resource::wakeup();
    }
    catch(AblePolecat_Command_Exception $Exception) {
      $pass = FALSE;
    }
    return $pass;
  }
}