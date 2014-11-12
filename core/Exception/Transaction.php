<?php
/**
 * @file      polecat/core/Exception/Transaction.php
 * @brief     Exceptions thrown by Able Polecat Transaction.
  *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

class AblePolecat_Transaction_Exception extends AblePolecat_Exception {
  const CODE_DATABASE_ERROR     = 0x002710;
}