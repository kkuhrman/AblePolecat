<?php
/**
 * @file      polecat/core/Resource/Restricted.php
 * @brief     Default base class for restricted resources.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Execute.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Open.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Read.php')));
require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Write.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Resource.php');

interface AblePolecat_Resource_RestrictedInterface extends AblePolecat_ResourceInterface {
  
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
   * Verifies that agent or role has given permission.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   * @param string $requestedConstraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   *
   * @return bool TRUE if $Subject has given permission, otherwise FALSE.
   * @throw AblePolecat_AccessControl_Exception if $Authority is not privileged to access permissions.
   */
  public function hasPermission(
    AblePolecat_AccessControl_SubjectInterface $Subject,
    $requestedConstraintId 
  );
}

abstract class AblePolecat_Resource_RestrictedAbstract 
  extends AblePolecat_ResourceAbstract 
  implements AblePolecat_Resource_RestrictedInterface {
  
  /**
   * @var resource Instance of singleton.
   */
  protected static $Resource = NULL;
  
  /**
   * @var Constraints assigned to resource.
   */
  private $Constraints;
  
  /**
   * @var Array Cache security tokens.
   * @todo: seems a waste to keep making round trips for these.
   */
  private $SecurityTokens;
  
  /**
   * @var AblePolecat_AccessControl_ConstraintInterface.
   */
  private $WakeupAccessRequest;
    
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_Resource_Util
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    $Resource = NULL;
    
    if (!isset(self::$Resource)) {
      throw new AblePolecat_AccessControl_Exception("Restricted resource sub-class must be created prior to calling parent wakeup method.");
    }
    else {
      if (self::$Resource->hasPermission($Subject, self::$Resource->getWakeupAccessRequest())) {
        $Resource = self::$Resource;
      }
      else {
        throw new AblePolecat_AccessControl_Exception(
          AblePolecat_AccessControl_Exception::formatDenyAccessMessage($Subject, self::$Resource)
        );
      }
    }
    return $Resource;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Resource_RestrictedInterface.
   ********************************************************************************/
  
  /**
   * Verifies that agent or role has given permission.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   * @param string $requestedConstraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   *
   * @return bool TRUE if $Subject has given permission, otherwise FALSE.
   * @throw AblePolecat_AccessControl_Exception if $Authority is not privileged to access permissions.
   */
  public function hasPermission(
    AblePolecat_AccessControl_SubjectInterface $Subject,
    $requestedConstraintId 
  ) {
    //
    // default deny
    //
    $hasPermission = FALSE;    
    $subjectId = $Subject->getId();
    
    //
    // Before making a trip to db, see if constraint data is already cached.
    //
    $constraintSettings = $this->checkConstraintSettings($requestedConstraintId, $subjectId);
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
        self::CONSTRAINT_INFO => self::lookupConstraint($requestedConstraintId),
        self::CONSTRAINT_RES  => array(),
      );
        
      //
      // Lookup all constraints placed on requested resource.
      //
      $sql = __SQL()->
        select('constraintId', 'authorityId')->
        from('constraint')->
        where(sprintf("resourceId = '%s'", $this->getId()));
      $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), $sql);
      if ($CommandResult->success()) {
        $results = $CommandResult->value();
        foreach($results as $key => $Record) {
          isset($Record['constraintId']) ? $constraintId = $Record['constraintId'] : $constraintId = NULL;
          isset($Record['authorityId']) ? $authorityId = $Record['authorityId'] : $authorityId = NULL;
          $this->setConstraint($constraintId, $authorityId);
        }
      }
      
      //
      // Lookup all permissions granted given subject for requested resource.
      //
      $sql = __SQL()->
        select('constraintId', 'subjectId', 'authorityId')->
        from('permission')->
        where(sprintf("resourceId = '%s'", $this->getId()));
      $CommandResult = AblePolecat_Command_DbQuery::invoke(AblePolecat_AccessControl_Agent_User::wakeup(), $sql);
      if ($CommandResult->success()) {
        $results = $CommandResult->value();
        foreach($results as $key => $Record) {
          isset($Record['constraintId']) ? $constraintId = $Record['constraintId'] : $constraintId = NULL;
          isset($Record['subjectId']) ? $subjectId = $Record['subjectId'] : $subjectId = NULL;
          isset($Record['authorityId']) ? $authorityId = $Record['authorityId'] : $authorityId = NULL;
          $this->setPermission($constraintId, $subjectId, $authorityId);
        }
      }
      
      //
      // Finally, check if subject is exempt from given constraint on requested resource.
      //
      $constraintSettings = $this->checkConstraintSettings($requestedConstraintId, $subjectId);
      $hasPermission = $constraintSettings[self::PERMISSION_EXISTS];
    }
    
    return $hasPermission;
  }
  
  /**
   * Indicates whether given agent or role has requested access to this resource.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   * @param mixed $Constraint Instance of AblePolecat_AccessControl_ConstraintInterface or corresponding ID.
   *
   * @return bool TRUE if given agent or role has requested access to resource, otherwise FALSE.
   */
  public static function hasAccess(
    AblePolecat_AccessControl_SubjectInterface $Subject = NULL,
    $Constraint = NULL
  ) {
    
    $hasAccess = FALSE;
    
    if (isset(self::$Resource)) {
      //
      // If security token is already cached for given subject...
      //
      if (isset($Subject) && isset(self::$Resource->SecurityTokens) && isset(self::$Resource->SecurityTokens[$Subject->getId()])) {
        $hasAccess = TRUE;
      }
      else {
        //
        // Otherwise, check access control...
        //
        $constraintId = NULL;
        if (isset($Constraint) && is_a($Constraint, 'AblePolecat_AccessControl_ConstraintInterface')) {
          $constraintId = $Constraint::getId();
        }
        else if (isset($Constraint) && is_scalar($Constraint)) {
          $constraintId = $Constraint;
        }
        else {
          $constraintId = AblePolecat_AccessControl_Constraint_Open::getId();
        }
        if (isset($Subject)) {
          $CommandResult = AblePolecat_Command_GetAccessToken::invoke($Subject, $Subject->getId(), self::$Resource->getId(), $constraintId);
          if ($CommandResult->success()) {
            if (!isset(self::$Resource->SecurityTokens)) {
              self::$Resource->SecurityTokens = array();
            }
            if (!isset(self::$Resource->SecurityTokens[$Subject->getId()])) {
              self::$Resource->SecurityTokens[$Subject->getId()] = $CommandResult->value();
            }
            $hasAccess = TRUE;
          }
        }
      }
    }
    return $hasAccess;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Helper function with dual role - check if constraint exists on resource AND if subject is exempt from constraint.
   *
   * @param string $constraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   * @param string $subjectId AblePolecat_AccessControl_SubjectInterface->getId() or Array of ids.
   * 
   * @return Array[self::CONSTRAINT_CACHED => bool, self::CONSTRAINT_EXISTS => bool, self::PERMISSION_EXISTS => bool]
   */
  protected function checkConstraintSettings(
    $constraintId, 
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
      if (isset($this->Constraints[$constraintId][self::CONSTRAINT_RES])) {
        $settings[self::CONSTRAINT_EXISTS] = TRUE;
        $settings[self::PERMISSION_EXISTS] = FALSE; // constraint placed on resource, subject must be explicitly exempt
        if (isset($this->Constraints[$constraintId][self::CONSTRAINT_RES][self::CONSTRAINT_PERM][$subjectId])) {
          $settings[self::PERMISSION_EXISTS] = $this->Constraints[$constraintId][self::CONSTRAINT_RES][self::CONSTRAINT_PERM][$subjectId];
        }
      }
    }
    return $settings;
  }
  
  /**
   * @return string ID of access control constraint (requesting permission).
   */
  protected function getWakeupAccessRequest() {
    return $this->WakeupAccessRequest;
  }
  
  /**
   * Sets given constraint on the resource.
   *
   * By default, setting constraint on this resource denies this action to all 
   * agents and roles. 
   *
   * @param mixed $requestedConstraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   * @param string $authorityId Access control subject making the request.
   *
   * @return bool TRUE if constraint is set, otherwise FALSE.
   * @throw AblePolecat_AccessControl_Exception if any constraint(s) cannot be set on given resource(s).
   */
  protected function setConstraint(
    $constraintId,
    $authorityId = NULL
  ) {
    
    //
    // @todo: validate authority
    //
    $constraints = $this->putArguments($constraintId);

    foreach ($constraints as $key => $constraint_id) {
      if (!isset($this->Constraints[$constraint_id])) {
        $this->Constraints[$constraint_id] = array(
          self::CONSTRAINT_INFO => $this->lookupConstraint($constraint_id),
          self::CONSTRAINT_RES  => array(),
        );
      }
      if (!isset($this->Constraints[$constraint_id][self::CONSTRAINT_RES])) {
        $this->Constraints[$constraint_id][self::CONSTRAINT_RES] = array(
          self::CONSTRAINT_AUTH => $authorityId,
          self::CONSTRAINT_PERM => array(),
        );
      }
    }
  }
  
  /**
   * Exempts subject from given constraint, if it exists, on requested resource.
   *
   * @param mixed $constraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   * @param mixed $subjectId AblePolecat_AccessControl_SubjectInterface->getId() or Array of ids.
   * @param string $authorityId Access control subject making the request.
   */
  protected function setPermission(
    $constraintId, 
    $subjectId,
    $authorityId = NULL
  ) {
    
    //
    // @todo: validate authority
    //
    $constraints = $this->putArguments($constraintId);
    $subjects = $this->putArguments($subjectId);

    foreach ($constraints as $key => $constraint_id) {
      if (isset($this->Constraints[$constraint_id])) {
        foreach($resources as $key => $resource_id) {
          if (isset($this->Constraints[$constraint_id][self::CONSTRAINT_RES])) {
            foreach($subjects as $key => $subject_id) {
              $this->Constraints[$constraint_id][self::CONSTRAINT_RES][self::CONSTRAINT_PERM][$subject_id] = TRUE;
            }
          }
        }
      }
    }
  }
  
  /**
   * @param string ID of access control constraint (requesting permission).
   */
  protected function setWakeupAccessRequest($WakeupAccessRequest) {
    $this->WakeupAccessRequest = $WakeupAccessRequest;
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
   * Looks up a constraint based on given id.
   *
   * @param mixed $requestedConstraintId i.e. AblePolecat_AccessControl_ConstraintInterface::getId() or Array of ids.
   *
   * @return AblePolecat_AccessControl_ConstraintInterface or NULL.
   */
  public static function lookupConstraint(
    $requestedConstraintId
  ) {
    //
    // @todo: Get more specific info about constraint.
    //
    return $requestedConstraintId;
  }
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    
    $this->Constraints = array();
    $this->SecurityTokens = array();
    $this->WakeupAccessRequest = AblePolecat_AccessControl_Constraint_Open::getId();
    
    //
    // Place constraints on built-in resources, which require authentication from an
    // authorized database user (e.g. 'util').
    //
    $this->setConstraint(
      array(
        AblePolecat_AccessControl_Constraint_Execute::getId(),
        AblePolecat_AccessControl_Constraint_Open::getId(),
        AblePolecat_AccessControl_Constraint_Read::getId(),
        AblePolecat_AccessControl_Constraint_Write::getId(),
      ), 
      $this->getId()
    );
  }
}