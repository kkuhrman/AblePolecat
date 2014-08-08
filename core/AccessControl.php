<?php
/**
 * @file      polecat/core/AccessControl.php
 * @brief     Manages access control for Able Polecat server.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Session.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Mode.php');

class AblePolecat_AccessControl extends AblePolecat_CacheObjectAbstract {
  
  /**
   * @var AblePolecat_AccessControl Instance of singleton.
   */
  private static $AccessControl;
  
  /**
   * @var Array() Registry of active access control agents.
   */
  private $Agents;
  
  /**
   * @var Array() Registry of active access control agent roles.
   */
  private $AgentRoles;
  
  /**
   * @var AblePolecat_Registry_Class Class Registry.
   */
  private $ClassRegistry;
  
  /**
   * @var AblePolecat_AccessControl_SubjectInterface.
   */
  private $CommandInvoker;
  
  /**
   * @var encapsulates session data.
   */
  private $Session;
  
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
   * @return AblePolecat_AccessControl Initialized access control service or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$AccessControl)) {
      if (isset($Subject) && is_a($Subject, 'AblePolecat_Server')) {
        self::$AccessControl = new AblePolecat_AccessControl();
        self::$AccessControl->CommandInvoker = $Subject;
      }
      else {
        $error_msg = sprintf("%s is not permitted to administer access control privileges.", AblePolecat_DataAbstract::getDataTypeName($Subject));
        throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
      }
    }
    return self::$AccessControl;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @return AblePolecat_Registry_Class.
   */
  protected function getClassRegistry() {
    if (!isset($this->ClassRegistry)) {
      $CommandResult = AblePolecat_Command_GetRegistry::invoke($this->getCommandInvoker(), 'AblePolecat_Registry_Class');
      if ($CommandResult->success()) {
        //
        // Save reference to class registry.
        //
        $this->ClassRegistry = $CommandResult->value();
      }
      else {
        throw new AblePolecat_AccessControl_Exception("Failed to retrieve class registry.");
      }
    }
    return $this->ClassRegistry;
  }
  
  /**
   * @return AblePolecat_AccessControl_SubjectInterface.
   */
  protected function getCommandInvoker() {
    return $this->CommandInvoker;
  }
  
  /**
   * Return access control agent for given environment context.
   *
   * @param AblePolecat_ModeInterface The environment in context.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent(AblePolecat_ModeInterface $Mode) {
    
    $Agent = NULL;
    $class_name = AblePolecat_DataAbstract::getDataTypeName($Mode);
    
    if (isset($this->Agents[$class_name])) {
      $Agent = $this->Agents[$class_name];
    }
    else {
      switch ($class_name) {
        default:
          break;
        case 'AblePolecat_Mode_Server':
          $agentClassName = 'AblePolecat_AccessControl_Agent_Server';
          break;
        case 'AblePolecat_Mode_Application':
          $agentClassName = 'AblePolecat_AccessControl_Agent_Application';
          break;
        case 'AblePolecat_Mode_User':
          $agentClassName = 'AblePolecat_AccessControl_Agent_User';
          break;
      }
      $Agent = $this->getClassRegistry()->loadClass($agentClassName, $Mode);
      if (isset($Agent)) {
        //
        // cache agent
        //
        $this->Agents[$class_name] = $Agent;
        
        //
        // cache agent roles
        //
        $this->getAgentRoles($Agent);
      }
    }
    if (!isset($Agent)) {
      $error_msg = sprintf("No access control agent defined for %s.", get_class($Mode));
      throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
    }
    return $Agent;
  }
  
  /**
   * Return active roles for given access control agent.
   *
   * @param AblePolecat_AccessControl_AgentInterface The access control subject (agent).
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgentRoles(AblePolecat_AccessControl_AgentInterface $Agent) {
    
    $AgentRoles = array();
    //
    // At present only user roles can be customized.
    //
    if (is_a($Agent, 'AblePolecat_AccessControl_Agent_User')) {
      try {
        if (isset($this->AgentRoles['AblePolecat_AccessControl_Agent_User'])) {
          //
          // Agent roles have already been cached.
          //
          $AgentRoles = $this->AgentRoles['AblePolecat_AccessControl_Agent_User'];
        }
        else {
          //
          // Agent roles have not been cached. Do that now.
          //
          $this->AgentRoles['AblePolecat_AccessControl_Agent_User'] = array();
          $sql = __SQL()->
            select('session_id', 'interface', 'userId', 'session_data')->
            from('role')->
            where(sprintf("session_id = '%s'", $this->getSession()->getId()));
          $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getCommandInvoker(), $sql);
          if ($CommandResult->success()) {
            $results = $CommandResult->value();
            try {
              foreach($results as $key => $role) {
                //
                // assign roles to agent
                //
                $roleClassName = $role['interface'];
                $Role = $this->getClassRegistry()->loadClass($roleClassName, $this->getSession());
                if (isset($Role)) {
                  $this->AgentRoles['AblePolecat_AccessControl_Agent_User'][] = $Role;
                }
                else {
                  //
                  // @todo: complain
                  //
                  AblePolecat_Command_Log::invoke($this, "Failed to load user role $roleClassName.", AblePolecat_LogInterface::WARNING);
                }
              }
            }
            catch (AblePolecat_Exception $Exception) {
              AblePolecat_Command_Log::invoke($this, $Exception->getMessage(), AblePolecat_LogInterface::WARNING);
            }
          }
          if (0 === count($this->AgentRoles['AblePolecat_AccessControl_Agent_User'])) {
            //
            // No roles assigned, assume anonymous user.
            //
            $Role = $this->getClassRegistry()->loadClass(
              'AblePolecat_AccessControl_Role_User_Anonymous', 
              $this->getSession()
            );
            $this->AgentRoles['AblePolecat_AccessControl_Agent_User'][] = $Role;
          }
          $AgentRoles = $this->AgentRoles['AblePolecat_AccessControl_Agent_User'];
        }
      }
      catch (AblePolecat_Exception $Exception) {
        throw new AblePolecat_AccessControl_Exception('Attempt to load agent roles prior to user mode being activated.');
      }
    }
    
    return $AgentRoles;
  }
  
  /**
   * @return AblePolecat_SessionInterface.
   */
  public function getSession() {
    return $this->Session;
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
    $this->AgentRoles = array();
    $this->ClassRegistry = NULL;
    $this->CommandInvoker = NULL;
    $this->Session = AblePolecat_Session::wakeup();
  }
}
