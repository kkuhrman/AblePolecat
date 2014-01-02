<?php
/**
 * @file: Command/Target.php
 * The recipient of a synchronous command within scope of single script execution.
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
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command.php')));

interface AblePolecat_Command_TargetInterface {
  
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