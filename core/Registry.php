<?php
/**
 * @file      polecat/core/Registry.php
 * @brief     Encapsulates a single core database table and provides system defaults.
 *
 * The Able Polecat core database comprises tables, which fall into one of two
 * main categories, registry and session. Session data includes HTTP requests, 
 * errors, logs, cached responses, access control settings and more. Registry 
 * data is a subset of environment configuration and includes PHP classes, 
 * components, connectors, resources, and responses. This data is initially saved
 * as XML in configuration files, which are used to populate the polecat database.
 * Classes implementing AblePolecat_RegistryInterface handle populating database
 * from configuration files and vice versa.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Schema.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Registry.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Server', 'Paths.php')));

interface AblePolecat_RegistryInterface 
  extends AblePolecat_CacheObjectInterface, AblePolecat_Database_InstallerInterface {
  
  /**
   * Registry keys.
   */
  const KEY_ARTICLE_ID            = 'id';
  const KEY_CLASS_NAME            = 'name';
  
  /**
   * Add a registry entry.
   *
   * @param AblePolecat_Registry_EntryInterface $RegistryEntry
   *
   * @throw AblePolecat_Registry_Exception If entry is incompatible.
   */
  public function addRegistration(AblePolecat_Registry_EntryInterface $RegistryEntry);
  
  /**
   * Retrieve registered object by given id.
   *
   * @param UUID $id Id of registered object.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function getRegistrationById($id);
  
  /**
   * Retrieve registered object by given name.
   *
   * @param string $name Name of registered object.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function getRegistrationByName($name);
  
  /**
   * Retrieve a list of registered objects corresponding to the given key name/value.
   *
   * @param string $keyName The name of a registry key.
   * @param string $keyValue Optional value of registry key.
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public function getRegistrations($key, $value = NULL);
}

abstract class AblePolecat_RegistryAbstract 
  extends AblePolecat_CacheObjectAbstract 
  implements AblePolecat_RegistryInterface {
  
  /**
   * @var Array Registry of classes which can be loaded.
   */
  private $Registrations;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_RegistryInterface.
   ********************************************************************************/
  
  /**
   * Add a registry entry.
   *
   * @param AblePolecat_Registry_EntryInterface $RegistryEntry
   *
   * @throw AblePolecat_Registry_Exception If entry is incompatible.
   */
  public function addRegistration(AblePolecat_Registry_EntryInterface $RegistryEntry) {
    
    isset($RegistryEntry->id) ? $id = $RegistryEntry->id : $id = NULL;
    isset($RegistryEntry->name) ? $name = $RegistryEntry->name : $name = NULL;
    
    if (isset($id) && isset($name)) {
      $this->Registrations[self::KEY_ARTICLE_ID][$id] = $RegistryEntry;
      $this->Registrations[self::KEY_CLASS_NAME][$name] = $RegistryEntry;
    }
    else {
      throw new AblePolecat_Registry_Exception(sprintf("%s must include properties 'id' and 'name'.",
        AblePolecat_Data::getDataTypeName($RegistryEntry)
      ));
    }
  }
  
  /**
   * Retrieve registered object by given id.
   *
   * @param UUID $id Id of registered object.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function getRegistrationById($id) {
    
    $RegistryEntry = NULL;
    
    if (isset($this->Registrations[self::KEY_ARTICLE_ID][$id])) {
      $RegistryEntry = $this->Registrations[self::KEY_ARTICLE_ID][$id];
    }
    return $RegistryEntry;
  }
  
  /**
   * Retrieve registered object by given name.
   *
   * @param string $name Name of registered object.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function getRegistrationByName($name) {
    
    $RegistryEntry = NULL;
    
    if (isset($this->Registrations[self::KEY_CLASS_NAME][$name])) {
      $RegistryEntry = $this->Registrations[self::KEY_CLASS_NAME][$name];
    }
    return $RegistryEntry;
  }
  
  /**
   * Retrieve a list of registered objects corresponding to the given key name/value.
   *
   * @param string $keyName The name of a registry key.
   * @param string $keyValue Optional value of registry key.
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public function getRegistrations($key, $value = NULL) {
    
    $Registrations = array();
    
    switch($key) {
      case self::KEY_ARTICLE_ID:
      case self::KEY_CLASS_NAME:
        if (isset($value)) {
          if (isset($this->Registrations[$key][$value])) {
            $Registrations = $this->Registrations[$key][$value];
          }
        }
        else {
          $Registrations = $this->Registrations[$key];
        }
        break;
    }
    return $Registrations;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Write a message to boot log and trigger PHP error.
   *
   * Most errors in registry entry classes will result in a fatal application
   * error. But most will also likely occur before standard error handling and
   * logging are operational. This method provides a means to catch these.
   *
   * @param string $message.
   */
  public static function triggerError($message) {
    AblePolecat_Log_Boot::wakeup()->
      putMessage(AblePolecat_LogInterface::ERROR, $message);
    trigger_error($message, E_USER_ERROR);
  }
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    //
    // Class registration.
    //
    $this->Registrations = array(
      self::KEY_ARTICLE_ID => array(),
      self::KEY_CLASS_NAME => array(),
    );
  }
}