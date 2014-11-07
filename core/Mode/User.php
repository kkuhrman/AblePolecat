<?php
/**
 * @file      polecat/core/Mode/User.php
 * @brief     Base class for User mode (password, security token protected etc).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Agent', 'User.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'User.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Application.php')));

abstract class AblePolecat_Mode_UserAbstract extends AblePolecat_Mode_ApplicationAbstract {
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $UserEnvironment;
  
  /********************************************************************************
   * Implementation of AblePolecat_Command_TargetInterface.
   ********************************************************************************/
   
  /**
   * Execute a command or pass back/forward chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public function execute(AblePolecat_CommandInterface $Command) {
    
    $Result = NULL;
    
    //
    // @todo: check invoker access rights
    //
    switch ($Command::getId()) {
      default:
        //
        // Not handled
        //
        break;
      case AblePolecat_Command_Log::UUID:
        //
        // Log
        //
        switch($Command->getEventSeverity()) {
          default:
            break;
          case AblePolecat_LogInterface::USER_INFO:
          case AblePolecat_LogInterface::USER_STATUS:
          case AblePolecat_LogInterface::USER_WARNING:
            //
            // Do not pass these to next link in CoR.
            //
            $Result = new AblePolecat_Command_Result(NULL, AblePolecat_Command_Result::RESULT_RETURN_SUCCESS);
            break;
        }
        break;
    }
    //
    // Pass command to next link in chain of responsibility
    //
    $Result = $this->delegateCommand($Command, $Result);
    return $Result;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // Load environment/configuration
    //
    //
    // $this->UserEnvironment = AblePolecat_Environment_User::wakeup($this->getAgent($this));
    
    AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'User mode is initialized.');
  }
}