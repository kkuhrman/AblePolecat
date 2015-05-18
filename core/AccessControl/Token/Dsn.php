<?php
/**
 * @file      polecat/core/AccessControl/Token/Dsn.php
 * @brief     Encapsulates credentials necessary to access typical database.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Token.php')));

interface AblePolecat_AccessControl_Token_DsnInterface
  extends AblePolecat_AccessControl_TokenInterface {
  
  /**
   * Serialization constants.
   */
  const PROPERTY_PASS = 'pass';
  const PROPERTY_USER = 'user';
  
  /**
   * @return string username.
   */
  public function getUsername();
  
  /**
   * @return string password.
   */
  public function getPassword();
}

class AblePolecat_AccessControl_Token_Dsn 
  extends AblePolecat_AccessControl_TokenAbstract
  implements AblePolecat_AccessControl_Token_DsnInterface {
  
  /**
   * @var string username.
   */
  private $userName;
  
  /**
   * @var string password.
   */
  private $password;
  
  /********************************************************************************
   * Implementation of Serializable
   ********************************************************************************/
   
  /**
   * @return string serialized representation of AblePolecat_Data_Primitive_ScalarAbstract.
   */
  public function serialize() {
    $data = array(
      self::PROPERTY_PASS => $this->getPassword(),
      self::PROPERTY_USER => $this->getUsername(),
    );
    return serialize($data);
  }
  
  /**
   * @return concrete instance of AblePolecat_Data_Primitive_ScalarAbstract.
   */
  public function unserialize($data) {
    $data = unserialize($data);
    isset($data[self::PROPERTY_PASS]) ? $this->setPassword($data[self::PROPERTY_PASS]) : NULL;
    isset($data[self::PROPERTY_USER]) ? $this->setUsername($data[self::PROPERTY_USER]) : NULL;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Token_DsnInterface.
   ********************************************************************************/
  
  /**
   * @return string username.
   */
  public function getUsername() {
    return $this->userName;
  }
  
  /**
   * @return string password.
   */
  public function getPassword() {
    return $this->password;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * @param string $userName.
   */
  public function setUsername($userName) {
    $this->userName = $userName;
  }
  
  /**
   * @param string $password.
   */
  public function setPassword($password) {
    $this->password = $password;
  }
}