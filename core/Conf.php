<?php
/**
 * @file: Conf.php
 * Base class for Able Polecat configuration objects.
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'CacheObject.php');
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));

interface AblePolecat_ConfInterface extends AblePolecat_AccessControl_ResourceInterface, AblePolecat_CacheObjectInterface {
  
  /**
   * write() parameters
   */
  const WRITE_PARAM_ELEMENT_NAME  = 'element_name';
  const WRITE_PARAM_ELEMENT_VALUE = 'element_value';
  
  /**
   * Core configuration file names.
   */
  const CONF_FILENAME_CORE        = 'core.xml';
  const CONF_FILENAME_HOST        = 'host.xml';
  
  /**
   * Element attribute names.
   */
  const ATTRIBUTE_AUTHOR  = 'author';  
  const ATTRIBUTE_CONNECTED = 'connected';
  const ATTRIBUTE_DESC    = 'description';
  const ATTRIBUTE_FULLPATH = 'fullPath';
  const ATTRIBUTE_ID      = 'id';
  const ATTRIBUTE_MODE    = 'mode';
  const ATTRIBUTE_NAME    = 'name';
  const ATTRIBUTE_PATH    = 'path';
  const ATTRIBUTE_REG     = 'register';
  const ATTRIBUTE_USE     = 'use';
  
  /**
   * Element names.
   */
  const CONF_NAMESPACE    = 'polecat';
  const ELEMENT_ROOT      = 'able_polecat';
  const ELEMENT_CORE      = 'core';
  const ELEMENT_DB        = 'database';
  const ELEMENT_DBS       = 'databases';
  const ELEMENT_HOST      = 'host';
  const ELEMENT_VERSION   = 'version';
  
  
  
  const ELEMENT_CHILDREN  = 'children';
  const ELEMENT_CLIENT    = 'client';
  
  const ELEMENT_MODULE    = 'module';
  const ELEMENT_MODULES   = 'modules';
  const ELEMENT_NAME      = 'name';
  const ELEMENT_PASS      = 'pass';
  const ELEMENT_USER      = 'user';
  const ELEMENT_VALUE     = 'value';
  const ELEMENT_ATTR      = 'attributes';
  const ELEMENT_CLASSES   = 'classes';
  const ELEMENT_CLASS     = 'class';
  const ELEMENT_LIBS      = 'libs';
  const ELEMENT_LIB       = 'lib';
  const ELEMENT_CLASSNAME = 'classname';
  const ELEMENT_INTERFACE = 'interface';
  const ELEMENT_FILENAME  = 'filename';
  const ELEMENT_FULLPATH  = 'fullpath';
  const ELEMENT_CLASSMETH = 'classFactoryMethod';
  
  /**
   * @return Array Core class registry.
   */
  public function getCoreClasses();
  
  /**
   * @return Array Core database connection settings.
   */
  public function getCoreDatabaseConf();
  
  /**
   * @return Array List of supported interfaces.
   */
  public function getCoreInterfaces();
  
  /**
   * @return Array Core version.
   */
  public function getCoreVersion();
  
}

abstract class AblePolecat_ConfAbstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_ConfInterface {
}

/**
 * Exceptions thrown by configuration objects.
 */
class AblePolecat_Conf_Exception extends AblePolecat_Exception {
}