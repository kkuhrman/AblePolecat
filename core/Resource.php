<?php
/**
 * @file      polecat/core/Resource.php
 * @brief     The 'Model' part of the MVC triad aka a resource on the web.
 *
 *
 * According to Richardson/Ruby (@see ISBN 978-0-596-52926-0), a Resource Oriented
 * Architecture involves four concepts:
 * 1. Resource (similar to data "Model", as in "MVC", in fossil talk).
 * 2. URI (where it is located on the web, the address @see http://www.w3.org/Addressing/)
 * 3. Representation (similar to "View", as in "MVC", in fossil talk).
 * 4. Links between Resources (e.g. previous/next links on paginated result set).
 * And also four properties:
 * 1. Addressability
 * 2. Statelessness
 * 3. Connectedness
 * 4. A uniform interface
 *
 * @todo: and the REST (pun intended)
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */ 

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'CacheObject.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Structure.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Data', 'Scalar', 'String.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'Resource.php')));

interface AblePolecat_ResourceInterface 
  extends AblePolecat_CacheObjectInterface, 
          AblePolecat_Data_StructureInterface, 
          AblePolecat_AccessControl_ResourceInterface {
  
  /**
   * Returns resource URI.
   *
   * @return string URI or NULL.
   */
  public function getUri();
}

abstract class AblePolecat_ResourceAbstract 
  extends AblePolecat_Data_StructureAbstract 
  implements AblePolecat_ResourceInterface {
  
  /**
   * @var AblePolecat_AccessControl_SubjectInterface Typically, the subject passed to wakeup().
   */
  private $CommandInvoker;
  
  /**
   * @var string URI.
   */
  private $uri;
  
  /**
   * Validates request URI path to ensure resource request can be fulfilled.
   *
   * @throw AblePolecat_Resource_Exception If request URI path is not validated.
   */
  abstract protected function validateRequestPath();
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
  }
   
  /********************************************************************************
   * Implementation of AblePolecat_ResourceInterface
   ********************************************************************************/
    
  /**
   * Returns resource URI.
   *
   * @return string URI or NULL.
   */
  public function getUri() {
    return $this->uri;
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Default command invoker.
   *
   * @return AblePolecat_AccessControl_SubjectInterface or NULL.
   */
  protected function getDefaultCommandInvoker() {
    return $this->CommandInvoker;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    
    //
    // throw exception if request URI path is not valid for resource
    //
    $this->validateRequestPath();
    $this->uri = AblePolecat_Host::getRequest()->getBaseUrl() . AblePolecat_Host::getRequest()->getRequestPath(TRUE);
  }
  
  /**
   * Sets the default command handlers (invoker/target).
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker
   */
  protected function setDefaultCommandInvoker(AblePolecat_AccessControl_SubjectInterface $Invoker) {
    $this->CommandInvoker = $Invoker;
  }
	
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    $args = func_get_args();
    if (isset($args[0]) && is_a($args[0], 'AblePolecat_AccessControl_SubjectInterface')) {
      $this->CommandInvoker = $args[0];
    }
    else {
      $this->CommandInvoker = NULL;
    }
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}