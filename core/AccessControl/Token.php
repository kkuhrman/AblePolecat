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
  /**
   * Creational method.
   *
   * @return AblePolecat_AccessControl_TokenInterface Concrete instance of class.
   */
  public static function create();
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
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Set user id.
   *
   * @return string $id.
   */
  protected function setId($id) {
    $this->id = $id;
  }
  
  /**
   * Set user name.
   *
   * @return string $name.
   */
  protected function setName($name) {
    $this->name = $name;
  }

  /**
   * Extends __construct().
   * Sub-classes initialize properties here.
   */
  protected function initialize() {
    //
    // Article properties.
    //
    $this->id = NULL;
    $this->name = NULL;
  }
  
  /********************************************************************************
   * Constructor/destructor.
   ********************************************************************************/
  
  /**
   * Access control tokens must be created by create().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize(), create().
   */
  final protected function __construct() {
    $this->initialize();
  }
  
  /**
   * Serialization prior to going out of scope in sleep().
   */
  final public function __destruct() {
  }
}