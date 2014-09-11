<?php
/**
 * @file      polecat/Message.php
 * @brief     Interface for all Able Polecat messages passed to service bus.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Message.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Overloadable.php');

interface AblePolecat_MessageInterface extends AblePolecat_DynamicObjectInterface, AblePolecat_OverloadableInterface {
  const ENTITY_BODY   = 'BODY';
  const HEAD          = 'HEAD';
}

abstract class AblePolecat_MessageAbstract extends AblePolecat_DynamicObjectAbstract implements AblePolecat_MessageInterface {
}