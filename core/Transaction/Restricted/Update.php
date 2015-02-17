<?php
/**
 * @file      polecat/core/Transaction/Restricted/Update.php
 * @brief     Encloses update procedures within a transaction.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction', 'Restricted.php')));

class AblePolecat_Transaction_Restricted_Update extends AblePolecat_Transaction_RestrictedAbstract {
  
  /**
   * Constants.
   */
  const UUID = 'fb2a9ab0-b6b4-11e4-a12d-0050569e00a2';
  const NAME = 'AblePolecat_Transaction_Restricted_Update';

  const ARG_DB   = 'database-name';
  const ARG_USER = 'username';
  const ARG_PASS = 'password';
  const ARG_AUTH = 'authority';
  
  /**
   * @var AblePolecat_AccessControl_Agent_User Instance of singleton.
   */
  private static $Transaction;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
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
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$Transaction)) {
      //
      // Unmarshall (from numeric keyed index to named properties) variable args list.
      //
      $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
      self::$Transaction = new AblePolecat_Transaction_Restricted_Update($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
      self::prepare(self::$Transaction, $ArgsList, __FUNCTION__);
    }
    return self::$Transaction;
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
    
    switch ($this->getRequest()->getMethod()) {
      default:
        break;
      case 'GET':
        //
        // Resource request resolves to registered class name, try to load.
        // Attempt to load resource class
        //
        try {
          $resourceClassName = $this->getResourceRegistration()->getResourceClassName();
          if (isset($resourceClassName) && ($resourceClassName === 'AblePolecat_Resource_Restricted_Update')) {
            $Resource = AblePolecat_Resource_Core_Factory::wakeup(
              $this->getDefaultCommandInvoker(),
              'AblePolecat_Resource_Restricted_Update'
            );
            $this->setStatus(self::TX_STATE_COMPLETED);
          }
        }
        catch(AblePolecat_AccessControl_Exception $Exception) {
          $Resource = parent::run();
        }
        break;
      case 'POST':
        $referer = $this->getRequest()->getQueryStringFieldValue(AblePolecat_Transaction_RestrictedInterface::ARG_REFERER);
        if (isset($referer)) {
          $CoreDatabase = AblePolecat_Database_Pdo::wakeup($this->getAgent());
          switch($referer) {
            default:
              //
              // @todo: invalid referer for POST update
              //
              break;
            case AblePolecat_Resource_Restricted_Install::UUID:
              //
              // INSERT [lib]
              //
              AblePolecat_Registry_ClassLibrary::install($CoreDatabase);
              
              //
              // INSERT [class]
              //
              AblePolecat_Registry_Class::install($CoreDatabase);
              break;
            case self::UUID:
              //
              // UPDATE [lib]
              //
              AblePolecat_Registry_ClassLibrary::update($CoreDatabase);
              
              //
              // UPDATE [class]
              //
              AblePolecat_Registry_Class::update($CoreDatabase);
              break;
          }
        }
        //
        // @todo: status message of some kind?
        //
        $Resource = AblePolecat_Resource_Core_Factory::wakeup(
          $this->getDefaultCommandInvoker(),
          'AblePolecat_Resource_Core_Ack'
        );
        $this->setStatus(self::TX_STATE_COMPLETED);
        break;
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