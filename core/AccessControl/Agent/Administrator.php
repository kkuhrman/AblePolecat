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
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode', 'Session.php')));

class AblePolecat_AccessControl_Agent_Administrator extends AblePolecat_AccessControl_AgentAbstract {
  
  /**
   * Constraint data keys.
   */
  const CONSTRAINT_INFO   = 'info';         // Information about the specific constraint.
  const CONSTRAINT_RES    = 'resource';     // Resource on which constraint is placed.
  const CONSTRAINT_PERM   = 'permissions';  // Subjects exempt from given constraint on specific resource.
  const CONSTRAINT_AUTH   = 'authority';    // Authority placing constraint, granting permission, etc.
  
  /**
   * These constants help manage unnecessary trips to database.
   */
  const CONSTRAINT_CACHED = 'constraint_cached';
  const CONSTRAINT_EXISTS = 'constraint_exists';
  const PERMISSION_EXISTS = 'permission_exists';
  
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
  
  /**
   * @var Array() Registry of active access control agent roles.
   */
  private $AgentRoles;
  
  /**
   * @var AblePolecat_Registry_Class Class Registry.
   */
  private $ClassRegistry;
    
  /**
   * @var Constraints assigned to resource.
   */
  private $Constraints;
    
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
   * Verify that agent is authorized to assume given role.
   *
   * @param string $agentId ID of agent.
   * @param string $roleId ID of role.
   *
   * @return bool TRUE if role is authorized for agent, otherwise FALSE.
   */
  public function agentAuthorizedForRole($agentId, $roleId) {
    
    //
    // @todo:
    //
    $authorized = TRUE;
    return $authorized;
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
   * Verifies that agent or role has given permission.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Authority Access control subject making the request.
   * @param mixed $Subject Agent or role (either object or ID).
   * @param string $requestedConstraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   * @param string $resourceId i.e. AblePolecat_AccessControl_ResourceInterface::getId().
   *
   * @return bool TRUE if $Subject has given permission, otherwise FALSE.
   * @throw AblePolecat_AccessControl_Exception if $Authority is not privileged to access permissions.
   */
  public function hasPermission(
    AblePolecat_AccessControl_SubjectInterface $Authority,
    $Subject,
    $resourceId,
    $requestedConstraintId 
  ) {
    //
    // default deny
    // @todo: validate authority
    //
    $hasPermission = FALSE;
    
    $subjectId = NULL;
    if (is_a($Subject, 'AblePolecat_AccessControl_SubjectInterface ')) {
      $subjectId = $Subject::getId();
    }
    else if (is_scalar($Subject)) {
      $subjectId = $Subject;
    }
    
    //
    // Before making a trip to db, see if constraint data is already cached.
    //
    $constraintSettings = $this->checkConstraintSettings($requestedConstraintId, $resourceId, $subjectId);
    if ($constraintSettings[self::CONSTRAINT_CACHED]) {
      //
      // Constraint data is already cached, see if constraint is placed on resource.
      //
      if (!$constraintSettings[self::CONSTRAINT_EXISTS]) {
        //
        // No constraint placed on resource, subject has permission to access.
        //
        $hasPermission = TRUE;
      }
      else {
        //
        // Constraint placed on resource, see if subject has permission to access.
        //
        $hasPermission = $constraintSettings[self::PERMISSION_EXISTS];
      }
    }
    else {
      //
      // Regardless of what other constraints may be placed on resource, indicate the one in
      // scope here has at the very least been 'cached'.
      //
      $this->Constraints[$requestedConstraintId] = array(
        self::CONSTRAINT_INFO => self::lookupConstraint(self::getId(), $requestedConstraintId),
        self::CONSTRAINT_RES  => array(),
      );
        
      //
      // Lookup all constraints placed on requested resource.
      //
      $sql = __SQL()->
        select('constraintId', 'authorityId')->
        from('constraint')->
        where(sprintf("resourceId = '%s'", $resourceId));
      $CommandResult = AblePolecat_Command_DbQuery::invoke($this, $sql);
      if ($CommandResult->success()) {
        $results = $CommandResult->value();
        foreach($results as $key => $Record) {
          isset($Record['constraintId']) ? $constraintId = $Record['constraintId'] : $constraintId = NULL;
          isset($Record['authorityId']) ? $authorityId = $Record['authorityId'] : $authorityId = NULL;
          $this->setConstraint($authorityId, $constraintId, $resourceId);
        }
      }
      
      //
      // Lookup all permissions granted given subject for requested resource.
      //
      $sql = __SQL()->
        select('constraintId', 'subjectId', 'authorityId')->
        from('permission')->
        where(sprintf("resourceId = '%s'", $resourceId));
      $CommandResult = AblePolecat_Command_DbQuery::invoke($this, $sql);
      if ($CommandResult->success()) {
        $results = $CommandResult->value();
        foreach($results as $key => $Record) {
          isset($Record['constraintId']) ? $constraintId = $Record['constraintId'] : $constraintId = NULL;
          isset($Record['subjectId']) ? $subjectId = $Record['subjectId'] : $subjectId = NULL;
          isset($Record['authorityId']) ? $authorityId = $Record['authorityId'] : $authorityId = NULL;
          $this->setPermission($authorityId, $constraintId, $resourceId, $subjectId);
        }
      }
      
      //
      // Finally, check if subject is exempt from given constraint on requested resource.
      //
      $constraintSettings = $this->checkConstraintSettings($requestedConstraintId, $resourceId, $subjectId);
      $hasPermission = $constraintSettings[self::PERMISSION_EXISTS];
    }
    
    return $hasPermission;
  }
  
  /** 
   * Looks up a constraint based on given id.
   *
   * @param string $authorityId Access control subject making the request.
   * @param mixed $requestedConstraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   *
   * @return AblePolecat_AccessControl_ConstraintInterface or NULL.
   * @throw AblePolecat_AccessControl_Exception if $Authority is not permitted to lookup constraints.
   */
  public static function lookupConstraint(
    $authorityId,
    $requestedConstraintId
  ) {
    //
    // @todo: Get more specific info about constraint.
    //
    return $requestedConstraintId;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Helper function formats exception message in event of access control violation.
   *
   * @param mixed $Subject Subject attempting to access restricted object.
   * @param mixed $Object Object subject is attempting to access.
   * @param mixed $Authority Access control authority attempting to grant acccess.
   *
   * @throw string formatted message.
   */
  public static function formatDenyAccessMessage (
    AblePolecat_AccessControl_SubjectInterface $Subject = NULL,
    AblePolecat_AccessControl_ArticleInterface $Object = NULL, 
    AblePolecat_AccessControl_SubjectInterface $Authority = NULL) {
    
    $message = sprintf("[%s] identified by '%s' is denied access to [%s] identified by '%s'.",
      isset($Subject) ? $Subject::getName() : 'null',
      isset($Subject) ? $Subject::getId() : 'null',
      isset($Object) ? $Object::getName() : 'null',
      isset($Object) ? $Object::getId() : 'null'
    );
    if (isset($Authority)) {
      $message .= ' ' . sprintf("[%s] identified by '%s' is not authorized to grant this request.",
        $Authority::getName(),
        $Authority::getId()
      );
    }
    return $message;
  }
  
  /**
   * Helper function with dual role - check if constraint exists on resource AND if subject is exempt from constraint.
   *
   * @param string $constraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   * @param string $resourceId i.e. AblePolecat_AccessControl_ResourceInterface::getId() or Array of ids.
   * @param string $subjectId AblePolecat_AccessControl_SubjectInterface::getId() or Array of ids.
   * 
   * @return Array[self::CONSTRAINT_CACHED => bool, self::CONSTRAINT_EXISTS => bool, self::PERMISSION_EXISTS => bool]
   */
  protected function checkConstraintSettings(
    $constraintId, 
    $resourceId,
    $subjectId
  ) {
    
    $settings = array(
      self::CONSTRAINT_CACHED => FALSE,
      self::CONSTRAINT_EXISTS => FALSE, 
      self::PERMISSION_EXISTS => FALSE,
    );
    
    if (isset($this->Constraints[$constraintId])) {
      $settings[self::CONSTRAINT_CACHED] = TRUE;
      $settings[self::PERMISSION_EXISTS] = TRUE; // constraint looked up, not necessarily placed on resource
      if (isset($this->Constraints[$constraintId][self::CONSTRAINT_RES][$resourceId])) {
        $settings[self::CONSTRAINT_EXISTS] = TRUE;
        $settings[self::PERMISSION_EXISTS] = FALSE; // constraint placed on resource, subject must be explicitly exempt
        if (isset($this->Constraints[$constraintId][self::CONSTRAINT_RES][$resourceId][self::CONSTRAINT_PERM][$subjectId])) {
          $settings[self::PERMISSION_EXISTS] = $this->Constraints[$constraintId][self::CONSTRAINT_RES][$resourceId][self::CONSTRAINT_PERM][$subjectId];
        }
      }
    }
    return $settings;
  }
  
  /**
   * Return active roles for given access control agent.
   *
   * @param AblePolecat_AccessControl_AgentInterface The access control subject (agent).
   *
   * @return AblePolecat_AccessControl_AgentInterface.
   */
  protected function getAgentRoles(AblePolecat_AccessControl_AgentInterface $Agent) {
    
    $AgentRoles = array();
    //
    // At present only user roles can be customized.
    //
    if (is_a($Agent, 'AblePolecat_AccessControl_Agent_User')) {
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
          where(sprintf("session_id = '%s'", AblePolecat_Host::getSessionId()));
        $CommandResult = AblePolecat_Command_DbQuery::invoke($this, $sql);
        if ($CommandResult->success()) {
          $results = $CommandResult->value();
          try {
            foreach($results as $key => $role) {
              //
              // assign roles to agent
              //
              $roleClassName = $role['interface'];
              $Role = $this->getClassRegistry()->loadClass($roleClassName);
              if (isset($Role)) {
                $this->AgentRoles['AblePolecat_AccessControl_Agent_User'][] = $Role;
                $Agent->assignActiveRole($this, $Role);
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
          $Role = $this->getClassRegistry()->loadClass('AblePolecat_AccessControl_Role_User_Anonymous');
          $this->AgentRoles['AblePolecat_AccessControl_Agent_User'][] = $Role;
          $Agent->assignActiveRole($this, $Role);
        }
        $AgentRoles = $this->AgentRoles['AblePolecat_AccessControl_Agent_User'];
      }
    }
    
    return $AgentRoles;
  }
  
  /**
   * @return AblePolecat_Registry_Class.
   */
  protected function getClassRegistry() {
    
    if (!isset($this->ClassRegistry)) {
      $CommandResult = AblePolecat_Command_GetRegistry::invoke($this, 'AblePolecat_Registry_Class');
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
   * Helper function accepts string or array as input and returns array of string(s).
   * @throw AblePolecat_Command_Exception 
   */
  protected function putArguments($arguments) {
    
    $output = NULL;
    $invalid_type = 'NULL';
    
    if (isset($arguments)) {
      if (is_array($arguments)) {
        $output = array();
        foreach($arguments as $key => $argument)
          if (is_string($argument)) {
            $output[] = $argument;
          }
          else {
            $invalid_type = get_type($argument);
            $output = NULL;
          }
      }
      else if (is_string($arguments)) {
        $output = array($arguments);
      }
      else {
        $invalid_type = get_type($arguments);
      }
    }
    if (!isset($output)) {
      $message = "Access Control article id must be string. $invalid_type given.";
      throw new AblePolecat_AccessControl_Exception($message);
    }
    return $output;
  }
  
  /**
   * Sets given constraint on the resource.
   *
   * By default, setting constraint on this resource denies this action to all 
   * agents and roles. 
   *
   * @param string $authorityId Access control subject making the request.
   * @param mixed $requestedConstraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   * @param mixed $resourceId i.e. AblePolecat_AccessControl_ResourceInterface::getId() or Array of ids.
   *
   * @return bool TRUE if constraint is set, otherwise FALSE.
   * @throw AblePolecat_AccessControl_Exception if any constraint(s) cannot be set on given resource(s).
   */
  protected function setConstraint(
    $authorityId,
    $constraintId, 
    $resourceId
  ) {
    
    //
    // @todo: validate authority
    //
    $constraints = $this->putArguments($constraintId);
    $resources = $this->putArguments($resourceId);

    foreach ($constraints as $key => $constraint_id) {
      if (!isset($this->Constraints[$constraint_id])) {
        $this->Constraints[$constraint_id] = array(
          self::CONSTRAINT_INFO => self::lookupConstraint(self::getId(), $constraint_id),
          self::CONSTRAINT_RES  => array(),
        );
      }
      foreach($resources as $key => $resource_id) {
        if (!isset($this->Constraints[$constraint_id][self::CONSTRAINT_RES][$resource_id])) {
          $this->Constraints[$constraint_id][self::CONSTRAINT_RES][$resource_id] = array(
            self::CONSTRAINT_AUTH => $authorityId,
            self::CONSTRAINT_PERM => array(),
          );
        }
      }
    }
  }
  
  /**
   * Exempts subject from given constraint, if it exists, on requested resource.
   *
   * @param string $authorityId Access control subject making the request.
   * @param mixed $constraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   * @param mixed $resourceId i.e. AblePolecat_AccessControl_ResourceInterface::getId() or Array of ids.
   * @param mixed $subjectId AblePolecat_AccessControl_SubjectInterface::getId() or Array of ids.
   */
  protected function setPermission(
    $authorityId,
    $constraintId, 
    $resourceId,
    $subjectId
  ) {
    
    //
    // @todo: validate authority
    //
    $constraints = $this->putArguments($constraintId);
    $subjects = $this->putArguments($subjectId);
    $resources = $this->putArguments($resourceId);

    foreach ($constraints as $key => $constraint_id) {
      if (isset($this->Constraints[$constraint_id])) {
        foreach($resources as $key => $resource_id) {
          if (isset($this->Constraints[$constraint_id][self::CONSTRAINT_RES][$resource_id])) {
            foreach($subjects as $key => $subject_id) {
              $this->Constraints[$constraint_id][self::CONSTRAINT_RES][$resource_id][self::CONSTRAINT_PERM][$subject_id] = TRUE;
            }
          }
        }
      }
    }
  }
  
  
  
  /**
   * Extends __construct().
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    $this->Agents = array();
    $this->AgentRoles = array();
    $this->ClassRegistry = NULL;
    $this->Constraints = array();
  }
}