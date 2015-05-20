<?php
/**
 * @file      polecat/core/CacheObject.php
 * @brief     Any object, which can be cached to maintain state or improve performance.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */
 
// require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Host.php')));
 
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
   *       AblePolecat_Command_Log::invoke($this, $Exception->getMessage(), AblePolecat_LogInterface::WARNING);
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
   * @var Array Garbage collection - store cache objects in order created.
   */
  private static $garbageCollection = NULL;
  
  /**
   * @var AblePolecat_AccessControl_SubjectInterface Typically, the subject passed to wakeup().
   */
  private $CommandInvoker;
  
  /**
   * @var bool Internal flag helps avoid redundant calls to sleep().
   */
  private $asleep;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
   
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {   
    if (FALSE === $this->asleep) {
      AblePolecat_Mode_Server::logBootMessage(AblePolecat_LogInterface::STATUS, sprintf("%s->sleep().",
        AblePolecat_Data::getDataTypeName($this)
      ));
      $this->asleep = TRUE;
    }
    else {
      throw new AblePolecat_Exception(sprintf("%s->sleep() has already been called.",
        AblePolecat_Data::getDataTypeName($this)
      ));
    }
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  abstract protected function initialize();
  
  /**
   * Delete cache objects in order created.
   */
  public static function cleanup() {
    if (isset(self::$garbageCollection)) {
      $CacheObject = array_pop(self::$garbageCollection);
      while (isset($CacheObject)) {
        $CacheObject->sleep();
        // $CacheObject = NULL;
        $CacheObject = array_pop(self::$garbageCollection);
      }
      self::$garbageCollection = NULL;
      // exit(0);
    }
  }
  
  /**
   * Default command invoker.
   *
   * @return AblePolecat_AccessControl_SubjectInterface or NULL.
   */
  protected function getDefaultCommandInvoker() {
    return $this->CommandInvoker;
  }
  
  /**
   * Sets the default command handlers (invoker/target).
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker
   */
  protected function setDefaultCommandInvoker(AblePolecat_AccessControl_SubjectInterface $Invoker) {
    $this->CommandInvoker = $Invoker;
  }
	
  /********************************************************************************
   * Constructor/destructor.
   ********************************************************************************/
   
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), wakeup().
   */
  final protected function __construct() {
    $args = func_get_args();
    if (isset($args[0]) && is_a($args[0], 'AblePolecat_AccessControl_SubjectInterface')) {
      $this->setDefaultCommandInvoker($args[0]);
    }
    $this->asleep = FALSE;
    $this->initialize();
    if (!isset(self::$garbageCollection)) {
      self::$garbageCollection = array();
    }
    self::$garbageCollection[] = $this;
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
    // $this->sleep();
  }
}