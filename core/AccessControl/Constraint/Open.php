<?php
/**
 * @file: Open.php
 * Base class for constraint on opening an access controlled resource.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint.php')));

class AblePolecat_AccessControl_Constraint_Open implements AblePolecat_AccessControl_ConstraintInterface {
  
  /**
   * Constants.
   */
  const UUID = '8ac53be0-6117-11e2-bcfd-0800200c9a66';
  const NAME = 'open';
  
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