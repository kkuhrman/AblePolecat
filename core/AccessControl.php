<?php
/**
 * @file      polecat/core/AccessControl.php
 * @brief     Manages access control for Able Polecat server.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Session.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Environment.php');

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
      self::$AccessControl = new AblePolecat_AccessControl();
      //
      // @todo: load access control matrix
      //
      if (isset($Subject) && is_a($Subject, 'AblePolecat_Session')) {
        echo "your session id is " . AblePolecat_Session::getId();
      }
    }
    return self::$AccessControl;
  }
   
  /**
   * Return access control agent for given environment context.
   *
   * @param AblePolecat_EnvironmentInterface The environment in context.
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  public function getAgent(AblePolecat_EnvironmentInterface $Environment) {
    
    $Agent = NULL;
    $class_name = get_class($Environment);
    
    if (isset($this->Agents[$class_name])) {
      $Agent = $this->Agents[$class_name];
    }
    else {
      switch ($class_name) {
        default:
          break;
        case 'AblePolecat_Environment_Server':
          $agentClassName = 'AblePolecat_AccessControl_Agent_Server';
          break;
        case 'AblePolecat_Environment_Application':
          $agentClassName = 'AblePolecat_AccessControl_Agent_Application';
          break;
        case 'AblePolecat_Environment_User':
          $agentClassName = 'AblePolecat_AccessControl_Agent_User';
          break;
      }
      $reg = AblePolecat_Server::getClassRegistry()->registerLoadableClass($agentClassName, NULL, 'wakeup');
      $Agent = AblePolecat_Server::getClassRegistry()->loadClass($agentClassName);
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
      $error_msg = sprintf("No access control agent defined for %s.", get_class($Environment));
      throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
    }
    return $Agent;
  }
  
  /**
   * Return active roles for given access control agent.
   *
   * @param AblePolecat_EnvironmentInterface The environment in context.
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
          $AgentRoles = $this->AgentRoles['AblePolecat_AccessControl_Agent_User'];
        }
        else {
          $this->AgentRoles['AblePolecat_AccessControl_Agent_User'] = array();
          $Database = AblePolecat_Server::getDatabase(FALSE);
          if (!isset($Database)) {
            //
            // Possibly in install mode
            //
            AblePolecat_Server::getClassRegistry()->registerLoadableClass('AblePolecat_AccessControl_Role_User_Anonymous', NULL, 'wakeup');
            $Role = AblePolecat_AccessControl_Role_User_Anonymous::wakeup($this->getSession());
            $this->AgentRoles['AblePolecat_AccessControl_Agent_User'][] = $Role;
          }
          else {
            //
            // load user roles
            //
            $sql = __SQL()->
              select('session_id', 'interface', 'userId', 'session_data')->
              from('role')->
              where(sprintf("session_id = '%s'", $this->getSession()->getId()));
            $PreparedStatement = $Database->prepareStatement($sql);
            $result = $PreparedStatement->execute();
            if (!$result) {
              AblePolecat_Command_Log::invoke($this, serialize($PreparedStatement->errorInfo()), AblePolecat_LogInterface::WARNING);
            }
            else {
              $results = $PreparedStatement->fetchAll();
              if (count($results) == 0) {
                $sql = __SQL()->
                  insert('session_id', 'interface')->
                  into ('role')->
                  values($this->getSession()->getId(), 'AblePolecat_AccessControl_Role_User_Anonymous');
                $PreparedStatement = $Database->prepareStatement($sql);
                $result = $PreparedStatement->execute();
                if ($result) {
                  AblePolecat_Server::getClassRegistry()->registerLoadableClass('AblePolecat_AccessControl_Role_User_Anonymous', NULL, 'wakeup');
                  $Role = AblePolecat_AccessControl_Role_User_Anonymous::wakeup($this->getSession());
                  $this->AgentRoles['AblePolecat_AccessControl_Agent_User'][] = $Role;
                }
                else {
                  AblePolecat_Command_Log::invoke($this, serialize($PreparedStatement->errorInfo()), AblePolecat_LogInterface::WARNING);
                }
              }
              else {
                try {
                  foreach($results as $key => $role) {
                    //
                    // assign roles to agent
                    //
                    $roleClassName = $role['interface'];
                    
                    if (!AblePolecat_Server::getClassRegistry()->isLoadable($roleClassName)) {
                      $sql = __SQL()->
                        select('path', 'method')->
                        from('class')->
                        where(__SQLEXPR('name', '=', $roleClassName));
                      $pathStmt = $Database->prepareStatement($sql);
                      if ($pathStmt->execute()) {
                        $result = $pathStmt->fetch();
                        isset($result['path']) ? $path = $result['path'] : $path = NULL;
                        isset($result['method']) ? $method = $result['method'] : $method = 'wakeup';
                        $reg = AblePolecat_Server::getClassRegistry()->registerLoadableClass($roleClassName, $path, $method);
                      }
                    }
                    $Role = AblePolecat_Server::getClassRegistry()->loadClass($roleClassName, $this->getSession());
                    // $Role = $roleClassName::wakeup($this->getSession());
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
            }
          }
          $AgentRoles = $this->AgentRoles['AblePolecat_AccessControl_Agent_User'];
        }
      }
      catch (AblePolecat_Exception $Exception) {
        throw new AblePolecat_AccessControl_Exception('Attempt to load agent roles prior to user mode being activated.',
          AblePolecat_Error::BOOT_SEQ_VIOLATION
        );
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
    $this->Session = AblePolecat_Session::wakeup();
  }
}
