<?php
/**
 * @file      polecat/core/AccessControl/Article/Dynamic.php
 * @brief     Any access control article with an ID defined at run-time (e.g. resource).
 *
 * Classes implementing this interface must have an ID, which is unique to the run-time environment.
 * Another way to think of objects implementing this resource is those which use late name binding.
 * Examples are session, user, resource, etc.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article.php')));

interface AblePolecat_AccessControl_Article_DynamicInterface extends AblePolecat_AccessControl_ArticleInterface {
  
  /**
   * System unique ID.
   *
   * @return scalar Subject unique identifier.
   */
  public function getId();
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public function getName();
}