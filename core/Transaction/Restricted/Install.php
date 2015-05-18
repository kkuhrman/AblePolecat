<?php
/**
 * @file      polecat/core/Transaction/Restricted/Install.php
 * @brief     Encloses install procedures within a transaction.
 *
 * Because the install procedures involve creating, altering or dropping the
 * server database, it is one of the few objects in Able Polecat, which makes use
 * of the $_SESSION global variable.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted.php')));

class AblePolecat_Transaction_Restricted_Install extends AblePolecat_Transaction_RestrictedAbstract {
  
  /**
   * Registry article constants.
   */
  const UUID = '9e0398f2-604b-11e4-8bab-0050569e00a2';
  const NAME = 'AblePolecat_Transaction_Restricted_Install';
  
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
    $Transaction = new AblePolecat_Transaction_Restricted_Install($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
    self::prepare($Transaction, $ArgsList, __FUNCTION__);
    return $Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Run the install procedures.
   *
   * @return AblePolecat_ResourceInterface
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function run() {
    
    $Resource = NULL;
    
    $ResourceRegistration = $this->getResourceRegistration();
    if (isset($ResourceRegistration) && ($ResourceRegistration->getClassId() === AblePolecat_Resource_Restricted_Install::UUID)) {
      switch ($this->getRequest()->getMethod()) {
        default:
          break;
        case 'GET':
          //
          // Resource request resolves to registered class name, try to load.
          // Attempt to load resource class
          //
          try {
            $Resource = AblePolecat_Resource_Core_Factory::wakeup(
              $this->getDefaultCommandInvoker(),
              'AblePolecat_Resource_Restricted_Install'
            );
            $this->setStatus(self::TX_STATE_COMPLETED);
          }
          catch(AblePolecat_AccessControl_Exception $Exception) {
            $Resource = parent::run();
          }
          break;
        case 'POST':
          $Resource = parent::run();
          break;
      }
    }
    return $Resource;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Transaction_RestrictedInterface.
   ********************************************************************************/
  
  /**
   * @return UUID Id of redirect resource on authentication.
   */
  public function getRedirectResourceId() {
    return AblePolecat_Resource_Restricted_Update::UUID;
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