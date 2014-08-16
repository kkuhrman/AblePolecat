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
    if ($this->getStatus() == self::TX_STATE_COMPLETED) {
      $this->commit();
    }
    else {
      $this->save(__FUNCTION__);
    }
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
        
        self::$Transaction->setRequest($Request);
        
        //
        // Resume transaction or start new one
        //
        self::$Transaction->setAgent($Agent);
        $transactionId = self::$Transaction->getAgent()->getCurrentTransactionId();
        if (!isset($transactionId)) {
          $transactionId = uniqid();
        }
        self::$Transaction->setTransactionId($transactionId);
        self::$Transaction->start(__FUNCTION__);
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
    
    //
    // Extract the part of the URI, which defines the resource.
    //
    $request_path_info = $this->getRequest()->getRequestPathInfo();
    isset($request_path_info[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME]) ? $resource_name = $request_path_info[AblePolecat_Message_RequestInterface::URI_RESOURCE_NAME] : $resource_name  = NULL;    
    if (isset($resource_name)) {
      //
      // Look up (first part of) resource name in database
      //
      $sql = __SQL()->          
        select('resourceClassName', 'resourceAuthorityClassName', 'resourceDenyCode')->
        from('resource')->
        where("resourceName = '$resource_name'");      
      $CommandResult = AblePolecat_Command_DbQuery::invoke($this->getDefaultCommandInvoker(), $sql);
      $resourceClassName = NULL;
      $resourceAuthorityClassName = NULL;
      if ($CommandResult->success() && is_array($CommandResult->value())) {
        $classInfo = $CommandResult->value();
        isset($classInfo[0]['resourceClassName']) ? $resourceClassName = $classInfo[0]['resourceClassName'] : NULL;
        isset($classInfo[0]['resourceAuthorityClassName']) ? $resourceAuthorityClassName = $classInfo[0]['resourceAuthorityClassName'] : NULL;
      }
      
      if (isset($resourceClassName)) {
        //
        // Resource request resolves to registered class name, try to load.
        // Attempt to load resource class
        //
        try {
          $Resource = $this->getClassRegistry()->loadClass($resourceClassName, $this->getAgent());
          $this->setStatus(self::TX_STATE_COMPLETED);
        }
        catch(AblePolecat_AccessControl_Exception $Exception) {
          //
          // @todo: handle different resourceDenyCode
          //
          require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Error.php')));
          $Resource = AblePolecat_Resource_Error::wakeup();
        }
      }
      else {
        //
        // Request did not resolve to a registered resource class.
        // Return one of the 'built-in' resources.
        //
        if ($resource_name === AblePolecat_Message_RequestInterface::RESOURCE_NAME_HOME) {
          require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Ack.php')));
          $Resource = AblePolecat_Resource_Ack::wakeup();
        }
        else {
          require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Search.php')));
          $Resource = AblePolecat_Resource_Search::wakeup();
        }
      }
    }
    else {
      //
      // @todo: why would we ever get here but wouldn't it be bad to not return a resource?
      //
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