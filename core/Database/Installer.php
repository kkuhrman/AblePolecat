<?php
/**
 * @file      polecat/core/Database/Installer.php
 * @brief     Generates DDL or DML to install or update the project registry.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Static.php')));

interface AblePolecat_Database_InstallerInterface extends AblePolecat_AccessControl_Article_StaticInterface {
  /**
   * Install current schema on existing Able Polecat database.
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public static function install();
  
  /**
   * Update current schema on existing Able Polecat database.
   *
   * @throw AblePolecat_Database_Exception if update fails.
   */
  public static function update();
}