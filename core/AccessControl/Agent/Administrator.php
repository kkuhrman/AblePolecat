<?php
/**
 * @file      polecat/core/AccessControl/Agent/Administrator.php
 * @brief     Manages role based access control (RBAC).
 * 
 * 1. A subject can execute a transaction only if the subject has selected or 
 *    been assigned a role.
 * 2. A subject's active role must be authorized for the subject.
 * 3. A subject can execute a transaction only if the transaction is authorized 
 *    for the subject's active role.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Execute.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Open.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Read.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Write.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Session.php')));

class AblePolecat_AccessControl_Agent_Administrator extends AblePolecat_AccessControl_AgentAbstract
{
  
  /**
   * Access control id Constants.
   */
  const UUID = '80d8e560-22e9-11e4-8c21-0800200c9a66';
  const NAME = 'Able Polecat Access Control';
  
  /**
   * @var AblePolecat_AccessControl_Agent_Administrator Instance of singleton.
   */
  private static $Administrator;
  
  /**
   * @var Array() Registry of active access control agents.
   */
  private $Agents;
      
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_SubjectInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for agent.
   *
   * @return string Agent identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for agent.
   *
   * @return string Agent name.
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
   * @return AblePolecat_AccessControl_Agent_Administrator Initialized access control service or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Administrator)) {
      if (isset($Subject) && is_a($Subject, 'AblePolecat_Host')) {
        //
        // Intentionally do not pass AblePolecat_Host to constructor as this would save
        // it as default command invoker. By default, commands will be dispatched to top
        // of system CoR hierarchy.
        //
        self::$Administrator = new AblePolecat_AccessControl_Agent_Administrator();
        AblePolecat_Host::logBootMessage(AblePolecat_LogInterface::STATUS, 'Administrator agent initialized.');
      }
      else {
        $error_msg = sprintf("%s is not permitted to administer access control privileges.", AblePolecat_Data::getDataTypeName($Subject));
        throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
      }
    }
    return self::$Administrator;
  }
  
  /********************************************************************************
   * Access control administration functions.
   ********************************************************************************/
  
  /**
   * Return access control agent for given environment context.
   *
   * @param AblePolecat_ModeInterface The environment in context.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent(AblePolecat_ModeInterface $Mode) {
    
    $Agent = NULL;
    $class_name = AblePolecat_Data::getDataTypeName($Mode);
    
    if (isset($this->Agents[$class_name])) {
      $Agent = $this->Agents[$class_name];
    }
    else {
      switch ($class_name) {
        default:
          $agentClassName = 'AblePolecat_AccessControl_Agent_User';
          break;
        // case 'AblePolecat_Mode_Server':
          // $agentClassName = 'AblePolecat_AccessControl_Agent_Server';
          // break;
        // case 'AblePolecat_Mode_Application':
          // $agentClassName = 'AblePolecat_AccessControl_Agent_Application';
          // break;
        case 'AblePolecat_Mode_Session':
          $agentClassName = 'AblePolecat_AccessControl_Agent_User';
          break;
      }
      $Agent = $this->getClassRegistry()->loadClass($agentClassName, $this, $Mode);
      
      if (isset($Agent)) {
        //
        // cache agent
        //
        $this->Agents[$class_name] = $Agent;
      }
    }
    if (!isset($Agent)) {
      $error_msg = sprintf("No access control agent defined for %s.", get_class($Mode));
      throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
    }
    return $Agent;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->Agents = array();
  }
}