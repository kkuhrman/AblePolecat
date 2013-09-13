<?php
/**
 * @file: Article.php
 *
 * Similar to 'the' (definite), 'a'/'an' (indefinite) in English grammar.
 * Used to  indicate the type of reference (general, specific, etc) being 
 * made by the Subject, Object, Constraint etc. in an access control system.
 */

 interface AblePolecat_AccessControl_ArticleInterface {
  
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