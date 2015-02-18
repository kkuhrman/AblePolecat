<?php
/**
 * @file      polecat/core/AccessControl/Constraint/Read.php
 * @brief     Constraint on read.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint.php')));

class AblePolecat_AccessControl_Constraint_Read implements AblePolecat_AccessControl_ConstraintInterface {
  
  /**
   * Constants.
   */
  const UUID = 'e8c99c39-b7a3-11e4-a12d-0050569e00a2';
  const NAME = 'read';
  
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