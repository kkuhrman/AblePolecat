<?php
/**
 * Expression.php
 * Represents a valid query language expression (e.g. unary, binary).
 */

interface AblePolecat_QueryLanguage_ExpressionInterface {
  
  /**
   * @return query langauge expression as a string.
   */
  public function __toString();
}