<?php
/**
 * @file      polecat/core/AccessControl/Token.php
 * @brief     Encapsulates credentials necessary to access restricted resource.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Dynamic.php')));

interface AblePolecat_AccessControl_TokenInterface 
  extends AblePolecat_AccessControl_Article_DynamicInterface, Serializable {
}

abstract class AblePolecat_AccessControl_TokenAbstract
  implements AblePolecat_AccessControl_TokenInterface {
  
  /**
   * @var int id of token.
   */
  private $id;
  
  /**
   * @var string Name of token.
   */
  private $name;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'USER';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * Return token id.
   *
   * @return string Subject unique identifier.
   */
  public function getId() {
    return $this->id;
  }
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public function getName() {
    return $this->name;
  }
}