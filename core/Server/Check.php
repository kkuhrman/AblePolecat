<?php
/**
 * @file: Check.php
 * Encapsulates a single system check (test).
 */

interface AblePolecat_Server_CheckInterface {
  
  /**
   * @return TRUE if check passed, otherwise FALSE.
   */
  public static function go();
  
  /**
   * @return int Error code if check failed.
   */
  public static function getErrorCode();
  
  /**
   * @return string Error message if check failed.
   */
  public static function getErrorMessage();
}

abstract class AblePolecat_Server_CheckAbstract implements AblePolecat_Server_CheckInterface {
  
  /**
   * @var int Error code on check fail.
   */
  protected static $error_code = 0;
  
  /**
   * @var string Error message on check fail.
   */
  protected static $error_message = 'OK';
  
  /**
   * @return int Error code if check failed.
   */
  public static function getErrorCode() {
    return self::$error_code;
  }
  
  /**
   * @return string Error message if check failed.
   */
  public static function getErrorMessage() {
    return self::$error_message;
  }
}