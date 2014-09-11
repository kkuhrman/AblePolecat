<?php
/**
 * @file      polecat/core/Mode/Session.php
 * @brief     Base class for Session mode (password, security token protected etc).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'User.php')));

class AblePolecat_Mode_Session extends AblePolecat_Mode_UserAbstract {
  
  /**
   * Constants.
   */
  const UUID = 'bbea2770-39bb-11e4-916c-0800200c9a66';
  const NAME = 'Able Polecat Session Mode';
  
  /**
   * @var Instance of Singleton.
   */
  private static $SessionMode;
  
  /**
   * @var AblePolecat_EnvironmentInterface.
   */
  private $Environment;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_SubjectInterface.
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
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Session or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$SessionMode)) {
      try {
        //
        // Create instance of user mode
        //
        self::$SessionMode = new AblePolecat_Mode_Session($Subject);

        //
        // Set chain of responsibility relationship
        //
        $Subject->setForwardCommandLink(self::$SessionMode);
        self::$SessionMode->setReverseCommandLink($Subject);
        
        //
        // Load environment/configuration
        //
        //
        // self::$SessionMode->Environment = AblePolecat_Environment_User::wakeup(self::$SessionMode->Agent);
      }
      catch(Exception $Exception) {
        self::$SessionMode = NULL;
        throw new AblePolecat_Mode_Exception(
          'Failed to initialize session mode. ' . $Exception->getMessage(),
          AblePolecat_Error::BOOT_SEQ_VIOLATION
        );
      }
       
    }
      
    return self::$SessionMode;
  }
  
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
   * Request to server to issue command to chain of responsibility.
   *
   * @param AblePolecat_CommandInterface $Command
   *
   * @return AblePolecat_Command_Result
   */
  public static function dispatchCommand(AblePolecat_CommandInterface $Command) {
    
    //
    // Default FAIL.
    //
    $Result = new AblePolecat_Command_Result();
    
    if (isset(self::$SessionMode)) {
      $Result = self::$SessionMode->execute($Command);
    }
    return $Result;
  }
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    parent::initialize();
  }
}