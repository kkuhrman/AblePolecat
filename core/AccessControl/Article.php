<?php
/**
 * @file      polecat/core/AccessControl/Article.php
 * @brief     Similar to 'the' (definite), 'a'/'an' (indefinite) in English grammar.
 *
 * Used to  indicate the type of reference (general, specific, etc) being 
 * made by the Subject, Object, Constraint etc. in an access control system.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'AccessControl.php')));

interface AblePolecat_AccessControl_ArticleInterface {
  
  /**
   * Scope of operation.
   *
   * @return string SYSTEM | APPLICATION | USER | SESSION | SERVICE | RESOURCE.
   */
  public static function getScope();
}