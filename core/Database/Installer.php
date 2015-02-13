<?php
/**
 * @file      polecat/core/Database/Installer.php
 * @brief     Encapsulates the Able Polecat database schema.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database.php')));

interface AblePolecat_Database_InstallerInterface extends AblePolecat_AccessControl_Article_StaticInterface {
  /**
   * Install current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public static function install(AblePolecat_DatabaseInterface $Database);
}