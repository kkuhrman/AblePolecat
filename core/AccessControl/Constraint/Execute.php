<?php
/**
 * @file: Execute.php
 * Base class for constraint on opening an access controlled resource.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint.php')));

class AblePolecat_AccessControl_Constraint_Execute implements AblePolecat_AccessControl_ConstraintInterface {
  
  /**
   * Constants.
   */
  const UUID = 'execute';
  const NAME = 'execute';
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'RESOURCE';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for security constraint.
   *
   * @return string Constraint identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security constraint.
   *
   * @return string Constraint name.
   */
  public static function getName() {
    return self::NAME;
  }
}