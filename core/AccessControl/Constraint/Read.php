<?php
/**
 * @file: Read.php
 * Base class for constraint on reading an access controlled resource.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'AccessControl.php');

class AblePolecat_AccessControl_Constraint_Read implements AblePolecat_AccessControl_ConstraintInterface {
  
  /**
   * Constants.
   */
  const UUID = '6b71c7f0-63dc-11e2-bcfd-0800200c9a66';
  const NAME = 'read';
  
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