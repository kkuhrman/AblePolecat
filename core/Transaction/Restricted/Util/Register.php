<?php
/**
 * @file      polecat/core/Transaction/Restricted/Util/Register.php
 * @brief     Add certain registrations to master project configuration file.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted.php')));

class AblePolecat_Transaction_Restricted_Util_Register extends AblePolecat_Transaction_RestrictedAbstract {
  
  /**
   * Constants.
   */
  const UUID = 'aa94260f-cd9c-11e4-a12d-0050569e00a2';
  const NAME = 'AblePolecat_Transaction_Restricted_Util_Register';
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    $Transaction = new AblePolecat_Transaction_Restricted_Util_Register($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
    self::prepare($Transaction, $ArgsList, __FUNCTION__);
    return $Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Rollback
   */
  public function rollback() {
    //
    // @todo
    //
  }
  
  /**
   * Return the data model (resource) corresponding to a web request URI/path.
   *
   *
   * @return AblePolecat_ResourceInterface
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function run() {
    
    $Resource = NULL;
    AblePolecat_Debug::kill($this);
    return $Resource;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Transaction_RestrictedInterface.
   ********************************************************************************/
  
  /**
   * @return UUID Id of redirect resource on authentication.
   */
  public function getRedirectResourceId() {
    //
    // POST to self.
    //
    return '';
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