<?php
/**
 * @file      polecat/core/Registry/Entry.php
 * @brief     Encapsulates a record in one of the Able Polecat core registries.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'DynamicObject.php');

interface AblePolecat_Registry_EntryInterface extends AblePolecat_DynamicObjectInterface {}

abstract class AblePolecat_Registry_EntryAbstract extends AblePolecat_DynamicObjectAbstract implements AblePolecat_Registry_EntryInterface {
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
  }
}