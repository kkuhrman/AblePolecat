<?php
/**
 * @file      polecat/core/Resource/Restricted.php
 * @brief     Default base class for restricted resources.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Open.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Resource.php');

abstract class AblePolecat_Resource_RestrictedAbstract extends AblePolecat_ResourceAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  protected static $Resource = NULL;
  
  /**
   * @var Array Cache security tokens.
   * @todo: seems a waste to keep making round trips for these.
   */
  private $SecurityTokens;
    
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
      if (self::hasAccess($Subject)) {
        $Resource = self::$Resource;
      }
      else {
        throw new AblePolecat_AccessControl_Exception(
          AblePolecat_AccessControl_Agent_Administrator::formatDenyAccessMessage($Subject, self::$Resource)
        );
      }
    }
    return $Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
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
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    parent::initialize();
    $this->SecurityTokens = array();
  }
}