<?php
/**
 * @file      polecat/core/QueryLanguage/Expression.php
 * @brief     Represents a valid query language expression (e.g. unary, binary).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

interface AblePolecat_QueryLanguage_ExpressionInterface {
  
  /**
   * @return query langauge expression as a string.
   */
  public function __toString();
}