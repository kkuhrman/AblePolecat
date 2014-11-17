<?php
/**
 * @file      polecat/core/Dom/Element.php
 * @brief     Extends PHP DOMElement class.
 * 
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom', 'Node.php')));

interface AblePolecat_Dom_ElementInterface extends AblePolecat_Dom_NodeInterface {
}

abstract class AblePolecat_Dom_ElementAbstract 
  extends AblePolecat_Dom_NodeAbstract 
  implements AblePolecat_Dom_ElementInterface {
}