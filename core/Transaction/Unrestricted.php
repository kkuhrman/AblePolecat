<?php
/**
 * @file      polecat/core/Transaction/Unrestricted.php
 * @brief     Encapsulates GET request for unrestricted resource as transaction.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.3
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction.php')));

class AblePolecat_Transaction_Unrestricted extends  AblePolecat_TransactionAbstract {
  
  /**
   * Registry article constants.
   */
  const UUID = '7bf12d40-23df-11e4-8c21-0800200c9a66';
  const NAME = 'AblePolecat_Transaction_Unrestricted';
  
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
    $Transaction = new AblePolecat_Transaction_Unrestricted($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
    self::prepare($Transaction, $ArgsList, __FUNCTION__);
    return $Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Commit
   *
   * For GET transactions, there is nothing to persist other than the transaction status.
   */
  public function commit() {
    //
    // Parent updates transaction in database.
    //
    parent::commit();
  }
  
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
   * Able Polecat expects the part of the URI, which follows the host or virtual host
   * name to define a 'resource' on the system. This function returns the data (model)
   * corresponding to request. If no corresponding resource is located on the system, 
   * or if an application error is encountered along the way, Able Polecat has a few 
   * built-in resources to deal with these situations.
   *
   * NOTE: Although a 'resource' may comprise more than one path component (e.g. 
   * ./books/[ISBN] or ./products/[SKU] etc), an Able Polecat resource is identified by
   * the first part only (e.g. 'books' or 'products') combined with a UUID. Additional
   * path parts are passed to the top-level resource for further resolution. This is 
   * why resource classes validate the URI, to ensure it follows expectations for syntax
   * and that request for resource can be fulfilled. In short, the Able Polecat server
   * really only fulfils the first part of the resource request and delegates the rest to
   * the 'resource' itself.
   *
   * @see AblePolecat_ResourceAbstract::validateRequestPath()
   *
   * @return AblePolecat_ResourceInterface
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function run() {
    
    $Resource = NULL;
    
    $resourceClassId = $this->getResourceRegistration()->getClassId();
    $resourceClassRegistration = $this->getClassRegistry()->getRegistrationById($resourceClassId);
    if (isset($resourceClassRegistration)) {
      //
      // Resource request resolves to registered class name, try to load.
      // Attempt to load resource class
      //
      try {
        $Resource = $this->getClassRegistry()->loadClass($resourceClassRegistration, $this->getAgent());
        $Resource->setId($this->getResourceRegistration()->getId());
        $Resource->setName($this->getResourceRegistration()->getName());
        $this->setStatus(self::TX_STATE_COMPLETED);
      }
      catch(AblePolecat_AccessControl_Exception $Exception) {
        //
        // Return access denied notification.
        // @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
        // 403 means server will refuses to fulfil request regardless of authentication.
        //
        $Resource = AblePolecat_Resource_Core_Factory::wakeup(
          $this->getDefaultCommandInvoker(),
          'AblePolecat_Resource_Core_Error',
          'Access Denied',
          $Exception->getMessage()
        );
        $this->setStatusCode(403);
        $this->setStatus(self::TX_STATE_COMPLETED);
      }
    }
    else {
      //
      // Request did not resolve to a registered resource class.
      // Return one of the 'built-in' resources.
      //
      switch ($this->getResourceRegistration()->getName()) {
        default:
          $Resource = AblePolecat_Resource_Core_Factory::wakeup(
            $this->getDefaultCommandInvoker(),
            'AblePolecat_Resource_Core_Error',
            'Resource not found',
            sprintf("Able Polecat cannot locate resource given by [%s]", $this->getResourceRegistration()->getName())
          );
          $this->setStatus(self::TX_STATE_COMPLETED);
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_UTIL:
          //
          // @todo:
          //
          break;
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_ACK:
        case AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME:
          $Resource = AblePolecat_Resource_Core_Factory::wakeup(
            $this->getDefaultCommandInvoker(),
            'AblePolecat_Resource_Core_Ack'
          );
          $this->setStatus(self::TX_STATE_COMPLETED);
          break;
      }
    }
    return $Resource;
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