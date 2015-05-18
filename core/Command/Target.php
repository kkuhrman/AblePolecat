<?php
/**
 * @file      polecat/core/Command/Target.php
 * @brief     The recipient of a synchronous command within scope of single script execution.
 *
 * Implements the Chain of Responsibility (COR) design pattern by linking with other
 * command target objects.
 *
 * Similar to land/mobile telecommunications links, those in this implementation
 * use forward and reverse link to describe the relationship between superior to 
 * subordinate, and subordinate to superior respectively.
 *
 * The intended work flow of this method is subordinate calls on superior, passing
 * self as parameter. If approved to be next in COR, superior returns reference to
 * itself, which subordinate must save as reverse link. Otherwise, if link is not
 * permitted, an exception is thrown and the whole thing fails.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Static.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command.php')));

interface AblePolecat_Command_TargetInterface 
  extends AblePolecat_AccessControl_Article_StaticInterface,
          AblePolecat_CacheObjectInterface {
  
  const CMD_LINK_FWD    = 'forward';
  const CMD_LINK_REV    = 'reverse';
  
  /**
   * Execute a command or pass back/forward chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public function execute(AblePolecat_CommandInterface $Command);
  
  /**
   * Allow given subject to serve as direct subordinate in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended subordinate target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setForwardCommandLink(AblePolecat_Command_TargetInterface $Target);
  
  /**
   * Allow given subject to serve as direct superior in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended superior target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setReverseCommandLink(AblePolecat_Command_TargetInterface $Target);
}

abstract class AblePolecat_Command_TargetAbstract 
  extends AblePolecat_CacheObjectAbstract
  implements AblePolecat_Command_TargetInterface {
  
  /**
   * @var Next reverse target in command chain of responsibility.
   */
  private $Superior;
  
  /**
   * @var Next forward target in command chain of responsibility.
   */
  private $Subordinate;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Command_TargetInterface.
   ********************************************************************************/
  
  /**
   * Allow given subject to serve as direct subordinate in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended subordinate target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setForwardCommandLink(AblePolecat_Command_TargetInterface $Target) {
    
    $Super = NULL;
    
    if ($this->validateCommandLink($Target, AblePolecat_Command_TargetInterface::CMD_LINK_FWD)) {
      $Super = $this;
      $this->Subordinate = $Target;
    }
    else {
      $msg = sprintf("Attempt to set %s as forward command link to %s was refused.",
        get_class($Target),
        get_class($this)
      );
      throw new AblePolecat_Command_Exception($msg);
    }
    return $Super;
  }
  
  /**
   * Allow given subject to serve as direct superior in Chain of Responsibility.
   *
   * @param AblePolecat_Command_TargetInterface $Target Intended superior target.
   *
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setReverseCommandLink(AblePolecat_Command_TargetInterface $Target) {
    
    $Subordinate = NULL;
    
    //
    // Only application mode can serve as next in COR.
    //
    if ($this->validateCommandLink($Target, AblePolecat_Command_TargetInterface::CMD_LINK_REV)) {
      $Subordinate = $this;
      $this->Superior = $Target;
    }
    else {
      $msg = sprintf("Attempt to set %s as forward command link to %s was refused.",
        get_class($Target),
        get_class($this)
      );
      throw new AblePolecat_Command_Exception($msg);
    }
    return $Subordinate;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Validates given command target as a forward or reverse COR link.
   *
   * @param AblePolecat_Command_TargetInterface $Target.
   * @param string $direction 'forward' | 'reverse'
   *
   * @return bool TRUE if proposed COR link is acceptable, otherwise FALSE.
   */
  protected function validateCommandLink(AblePolecat_Command_TargetInterface $Target, $direction) {
    return TRUE;
  }
  
  /**
   * Send command or forward or back the chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   * @param AblePolecat_Command_Result $Result Optional, do delegate if set.
   *
   * @return AblePolecat_Command_Result
   */
  protected function delegateCommand(AblePolecat_CommandInterface $Command, AblePolecat_Command_Result $Result = NULL) {
    
    if (!isset($Result)) {
      $Target = NULL;
            
      try {
        switch ($Command::direction()) {
          default:
            break;
          case AblePolecat_Command_TargetInterface::CMD_LINK_FWD:
            $Target = $this->getForwardCommandLink();
            break;
          case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
            $Target = $this->getReverseCommandLink();
            break;
        }
      }
      catch(AblePolecat_Command_Exception $Exception) {
        //
        // This target is the end of the CoR and the command has not been handled.
        //
        $Result = new AblePolecat_Command_Result();
      }
      if (isset($Target)) {
        $Result = $Target->execute($Command);
      }
    }
    return $Result;
  }
  
  /**
   * Get forward (subordinate) link in command-processing Chain of Responsibility.
   *
   * @return AblePolecat_Command_TargetInterface $Target 
   */
  protected function getForwardCommandLink() {
    
    $Subordinate = $this->Subordinate;
    if (!isset($Subordinate)) {
      $message = 'Attempt to access ' . __METHOD__ . ' when no forward command link has been defined.';
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
      throw new AblePolecat_Command_Exception($message);
    }
    return $Subordinate;
  }
  
  /**
   * Get reverse (superior) link in command-processing Chain of Responsibility.
   *
   * @return AblePolecat_Command_TargetInterface $Target 
   */
  protected function getReverseCommandLink() {
    $Superior = $this->Superior;
    if (!isset($Superior)) {
      $message = 'Attempt to access ' . __METHOD__ . ' when no reverse command link has been defined.';
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, $message);
      throw new AblePolecat_Command_Exception($message);
    }
    return $Superior;
  }
}