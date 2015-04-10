<?php
/**
 * @file      polecat/core/AccessControl/Article/Static.php
 * @brief     Any access control with a static ID (e.g. UUID) such as a system command.
 *
 * Classes implementing this interface must have a UUID or reserved keyword (e.g. resource 
 * constraint such as read, write, execute, etc). Another way to think of objects implementing
 * this resource is those which use early name binding. Examples are access control constraint,
 * system command, command target and code modules (e.g. transaction classes).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article.php')));

interface AblePolecat_AccessControl_Article_StaticInterface extends AblePolecat_AccessControl_ArticleInterface {
  
  /**
   * Ideally unique id will be UUID.
   *
   * @return string Subject unique identifier.
   */
  public static function getId();
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public static function getName();
}