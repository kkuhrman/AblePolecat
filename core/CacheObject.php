<?php
/**
 * @file: CacheObject.php
 * Any object, which can be cached to maintain state or improve performance.
 */
 
include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Subject.php')));
 
interface AblePolecat_CacheObjectInterface {
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL);
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * Best practice wakeup() will return the object fully initialized and ready to work.
   * Should anything prevent this, wakeup() should throw an exception so as to prevent
   * method calls on non objects or objects that are not properly initialized.
   * Thus, wakeup() should always be called within a try/catch block and chaining is
   * encouraged. For example:
   *    try {
   *        MyObject::wakeup()->myMethod();
   *    }
   *    catch(AblePolecat_Exception $Exception) {
   *       AblePolecat_Server::log(AblePolecat_LogInterface::WARNING, $Exception->getMessage());
   *    }
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL);
}

abstract class AblePolecat_CacheObjectAbstract implements AblePolecat_CacheObjectInterface {
  
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  abstract protected function initialize();
	
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    $this->sleep();
  }
}