<?php
/**
 * @file      polecat/core/Savepoint.php
 * @brief     Encapsulates a transaction save point.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Token.php')));

interface AblePolecat_SavepointInterface 
  extends AblePolecat_AccessControl_TokenInterface {
    
  /**
   * Return the id of the parent transaction.
   *
   * @return string Id of transaction.
   */
  public function getTransactionId();
}

class AblePolecat_Savepoint 
  extends AblePolecat_AccessControl_TokenAbstract
  implements AblePolecat_SavepointInterface {
  
  /**
   * Article constants.
   */
  const CLASS_ID    = '04a4fe12-0475-11e5-9add-0050569e00a2';
  const CLASS_NAME  = 'AblePolecat_Savepoint';
  
  /**
   * Serialization constants.
   */
  const PROPERTY_ID = 'id';
  const PROPERTY_NAME = 'name';
  const PROPERTY_TRXID = 'transaction id';
  
  /**
   * @var string Id of parent transaction.
   */
  private $transactionId;
  
  /********************************************************************************
   * Implementation of Serializable
   ********************************************************************************/
   
  /**
   * @return string serialized representation of AblePolecat_Data_Primitive_ScalarAbstract.
   */
  public function serialize() {
    $data = array(
      self::PROPERTY_ID => $this->getId(),
      self::PROPERTY_NAME => $this->getName(),
      self::PROPERTY_TRXID => $this->getTransactionId(),
    );
    return serialize($data);
  }
  
  /**
   * @return concrete instance of AblePolecat_Data_Primitive_ScalarAbstract.
   */
  public function unserialize($data) {
    $data = unserialize($data);
    isset($data[self::PROPERTY_ID]) ? $this->setId($data[self::PROPERTY_ID]) : NULL;
    isset($data[self::PROPERTY_NAME]) ? $this->setName($data[self::PROPERTY_NAME]) : NULL;
    isset($data[self::PROPERTY_TRXID]) ? $this->setTransactionId($data[self::PROPERTY_TRXID]) : NULL;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SESSION';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_TokenInterface.
   ********************************************************************************/
   
  /**
   * Creational method.
   *
   * @param AblePolecat_TransactionInterface $Transaction Parent transaction of save point.
   * @param string $name Name of save point.
   *
   * @return AblePolecat_Savepoint Concrete instance of class.
   */
  public static function create() {
    
    $Savepoint = NULL;
    
    //
    // Process constructor arguments.
    //
    $args = func_get_args();
    if (2 != count($args)) {
      $message = sprintf("%s expects two arguments. AblePolecat_TransactionInterface [parent transaction], string [name of save point].",
        __METHOD__
      );
      throw new AblePolecat_Transaction_Exception($message);
    }
    if (!is_a($args[0], 'AblePolecat_TransactionInterface')) {
      $message = sprintf("First argument passed to %s must implement AblePolecat_TransactionInterface. %s passed.",
        __METHOD__, AblePolecat_Data::getDataTypeName($args[0])
      );
      throw new AblePolecat_Transaction_Exception($message);
    }
    if (!is_scalar($args[1])) {
      $message = sprintf("Second argument passed to %s must be scalar. %s passed.",
        __METHOD__, AblePolecat_Data::getDataTypeName($args[1])
      );
      throw new AblePolecat_Transaction_Exception($message);
    }
    
    //
    // Create the new save point.
    //
    $Savepoint = new AblePolecat_Savepoint();
    $Savepoint->setId(uniqid());
    $Savepoint->setName($args[1]);
    $Savepoint->setTransactionId($args[0]->getId());
    return $Savepoint;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
   
  /**
   * Return the id of the parent transaction.
   *
   * @return string Id of transaction.
   */
  public function getTransactionId() {
    return $this->transactionId;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Set parent transaction id.
   *
   * @return string $id.
   */
  protected function setTransactionId($id) {
    $this->transactionId = $id;
  }

  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->transactionId = NULL;
  }
}