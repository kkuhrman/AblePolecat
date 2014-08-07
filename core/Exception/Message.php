<?php 
/**
 * @file      polecat/Exception/Message.php
 * @brief     Exceptions thrown by Able Polecat message (request/response) objects.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception.php')));

class AblePolecat_Message_Exception extends AblePolecat_Exception {
}