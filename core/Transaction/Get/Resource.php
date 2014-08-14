<?php
/**
 * @file      polecat/core/Transaction/Get/Resource.php
 * @brief     Encapsulates a GET request for a web resource as a transaction.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Get.php')));

class AblePolecat_Transaction_Get_Resource extends  AblePolecat_Transaction_GetAbstract {
  
  /**
   * Constants.
   */
  const UUID = '7bf12d40-23df-11e4-8c21-0800200c9a66';
  const NAME = 'GET resource transaction';
  
  /**
   * @var AblePolecat_AccessControl_Agent_User Instance of singleton.
   */
  private static $Transaction;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for agent.
   *
   * @return string Transaction identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for agent.
   *
   * @return string Transaction name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // @todo: save transaction state.
    //
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Transaction)) {
      $Args = func_get_args();
      isset($Args[0]) ? $Subject = $Args[0] : $Subject = NULL;
      isset($Args[1]) ? $Agent = $Args[1] : $Agent = NULL;
      isset($Args[2]) ? $Request = $Args[2] : $Request = NULL;
      if (isset($Subject) && is_a($Subject, 'AblePolecat_Command_TargetInterface')) {
        self::$Transaction = new AblePolecat_Transaction_Get_Resource($Subject);
        
        //
        // @todo: restore transaction state
        //
        // self::$Transaction->setRequest($Request);
        // self::$Transaction->setAgent($Agent);
        AblePolecat_Dom::kill($Agent);
      }
      else {
        $error_msg = sprintf("%s is not permitted to start or resume a transaction.", AblePolecat_DataAbstract::getDataTypeName($Subject));
        throw new AblePolecat_AccessControl_Exception($error_msg, AblePolecat_Error::ACCESS_DENIED);
      }
    }
    return self::$Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
   
  /**
   * Commit
   */
  public function commit() {
    //
    // @todo
    //
  }
  
  /**
   * Rollback
   */
  public function rollback() {
    //
    // @todo
    //
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}