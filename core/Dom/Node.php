<?php
/**
 * @file      polecat/core/Dom/Node.php
 * @brief     Extends PHP DOMNode class.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Structure.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom.php')));

interface AblePolecat_Dom_NodeInterface extends AblePolecat_Data_StructureInterface {
  /**
   * @return AblePolecat_Dom_NodeInterface
   */
  public static function create();
}

abstract class AblePolecat_Dom_NodeAbstract 
  extends AblePolecat_Data_StructureAbstract 
  implements AblePolecat_Dom_NodeInterface {
  
  final protected function __construct() {
  }
}