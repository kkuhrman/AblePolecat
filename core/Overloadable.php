<?php
/**
 * @file      polecat/Overloadable.php
 * @brief     Must define methods for marshalling variable argument lists passed to methods.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'ArgsList.php')));

interface AblePolecat_OverloadableInterface {
  
  /**
   * Marshall numeric-indexed array of variable method arguments.
   *
   * @param string $method_name __METHOD__ will render className::methodName; __FUNCTION__ is probably good enough.
   * @param Array $args Variable list of arguments passed to method (i.e. get_func_args()).
   * @param mixed $options Reserved for future use.
   *
   * @return Array Associative array representing [argument name] => [argument value]
   */
  public static function unmarshallArgsList($method_name, $args, $options = NULL);
}