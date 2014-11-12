<?php
/**
 * @file      polecat/core/Command/Chain.php
 * @brief     A double-linked list encapsulating command chain of responsibility.
 *
 * A command target is responsible for processing the command and returning the result.
 * An event handler is notified when a specific command is issued and is given an 
 * opportunity to do some work before or after the result is returned.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Command', 'Target.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Command.php')));

interface AblePolecat_Command_ChainInterface
  extends AblePolecat_AccessControl_Article_StaticInterface,
          AblePolecat_CacheObjectInterface {
  
  /**
   * Route command to appropriate starting point in CoR.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public static function dispatch(AblePolecat_CommandInterface $Command);
  
  /**
   * Get command target at the bottom of the stack (most 'superior').
   *
   * @return AblePolecat_Command_TargetInterface
   */
  public function getBottomCommandTarget();
  
  /**
   * Get command target at the top of the stack (most 'subordinate').
   *
   * @return AblePolecat_Command_TargetInterface
   */
  public function getTopCommandTarget();
  
  /**
   * Establish superior-subordinate relationship between two command targets.
   *
   * @param AblePolecat_Command_TargetInterface $Superior Intended superior target.
   * @param AblePolecat_Command_TargetInterface $Subordinate Intended subordinate target.
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setCommandLink(
    AblePolecat_Command_TargetInterface $Superior, 
    AblePolecat_Command_TargetInterface $Subordinate
  );
}

class AblePolecat_Command_Chain 
  extends AblePolecat_CacheObjectAbstract
  implements AblePolecat_Command_ChainInterface {
  
  const UUID = 'ea210bbf-69b2-11e4-b5a7-0050569e00a2';
  const NAME = 'AblePolecat_Command_Chain';
  
  /**
   * @var AblePolecat_Command_Chain Instance of singleton.
   */
  private static $CommandChain;
  
  /**
   * @var Array [target id => offset in CoR list].
   */
  private $targetOffsets;
  
  /**
   * @var SplDoublyLinkedList List of targets in CoR.
   */
  private $targetList;
  
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
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
   
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$CommandChain)) {
      self::$CommandChain = new AblePolecat_Command_Chain();
    }
    return self::$CommandChain;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Command_ChainInterface.
   ********************************************************************************/
  
  /**
   * Route command to appropriate starting point in CoR.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public static function dispatch(AblePolecat_CommandInterface $Command) {
    
    $CommandChain = self::wakeup();
    $CommandResult = NULL;
    $Target = NULL;
    
    switch ($Command::direction()) {
      default:
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_FWD:
        $Target = $CommandChain->getBottomCommandTarget();
        break;
      case AblePolecat_Command_TargetInterface::CMD_LINK_REV:
        $Target = $CommandChain->getTopCommandTarget();
        break;
    }
    if (isset($Target)) {
      $CommandResult = $Target->execute($Command);
    }
    else {
      $CommandResult = new AblePolecat_Command_Result();
    }
    
    return $CommandResult;
  }
  
  /**
   * Get command target at the bottom of the stack (most 'superior').
   *
   * @return AblePolecat_Command_TargetInterface
   */
  public function getBottomCommandTarget() {
    
    $Target = NULL;
    
    try {
      $Target = $this->targetList;
    }
    catch (RuntimeException $Exception) {
      throw new AblePolecat_Command_Exception('There are no command targets assigned to chain of responsibility.');
    }
    return $Target->bottom();
  }
  
  /**
   * Get command target at the top of the stack (most 'subordinate').
   *
   * @return AblePolecat_Command_TargetInterface
   */
  public function getTopCommandTarget() {
    
    $Target = NULL;
    
    try {
      $Target = $this->targetList->top();
    }
    catch (RuntimeException $Exception) {
      throw new AblePolecat_Command_Exception('There are no command targets assigned to chain of responsibility.');
    }
    return $Target;
  }
  
  /**
   * Establish superior-subordinate relationship between two command targets.
   *
   * @param AblePolecat_Command_TargetInterface $Superior Intended superior target.
   * @param AblePolecat_Command_TargetInterface $Subordinate Intended subordinate target.
   * @throw AblePolecat_Command_Exception If link is refused.
   */
  public function setCommandLink(
    AblePolecat_Command_TargetInterface $Superior, 
    AblePolecat_Command_TargetInterface $Subordinate
  ) {
    
    $violationMessage = FALSE;
    
    //
    // First, check targets are not identical.
    //
    if ($Superior::getId() === $Subordinate::getId()) {
      $violationMessage = sprintf("Cannot establish command link between target and itself (%s).",
        $Superior::getName()
      );
    }
    else {
      //
      // CoR must be empty OR Superior must already be set at top.
      //
      if ($this->targetList->isEmpty() == FALSE) {
        $Target = $this->targetList->top();
        if ($Superior::getId() != $Target::getId()) {
          $violationMessage = sprintf("Cannot establish command link between %s <- %s. Current top of CoR stack is %s.",
            $Superior::getName(),
            $Subordinate::getName(),
            $Target::getName()
          );
        }
        else if (isset($this->targetOffsets[$Subordinate::getId()])) {
          $violationMessage = sprintf("Cannot establish command link between %s <- %s because %s is already set in CoR at offset %d.",
            $Superior::getName(),
            $Subordinate::getName(),
            $Subordinate::getName(),
            $this->targetOffsets[$Subordinate::getId()]
          );
        }
        else {
          $this->targetList->push($Subordinate);
          $offset = $this->targetList->count() - 1;
          $this->targetOffsets[$Subordinate::getId()] = $offset;
          $Superior->setForwardCommandLink($Subordinate);
          $Subordinate->setReverseCommandLink($Superior);
        }
      }
      else {
        //
        // Target list is empty - no further restrictions on this link.
        // Superior target.
        //
        $this->targetList->push($Superior);
        $offset = $this->targetList->count() - 1;
        $this->targetOffsets[$Superior::getId()] = $offset;
        $Superior->setForwardCommandLink($Subordinate);
        
        //
        // Subordinate target.
        //
        $this->targetList->push($Subordinate);
        $offset = $this->targetList->count() - 1;
        $this->targetOffsets[$Subordinate::getId()] = $offset;
        $Subordinate->setReverseCommandLink($Superior);
      }
    }
    if ($violationMessage) {
      throw new AblePolecat_Command_Exception($violationMessage);
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    $this->targetList = new SplDoublyLinkedList();
    $this->targetOffsets = array();
  }
}