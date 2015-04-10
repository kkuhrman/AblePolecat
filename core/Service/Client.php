<?php
/**
 * @file      polecat/core/Service/Client.php
 * @brief     Base for web service clients.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Service', 'Initiator.php')));

/**
 * Manages a client connection to a web services provider.
 */
interface AblePolecat_Service_ClientInterface extends AblePolecat_Service_InitiatorInterface {
}

abstract class AblePolecat_Service_ClientAbstract extends AblePolecat_Service_InitiatorAbstract implements AblePolecat_Service_ClientInterface {
  
  /**
   * @var string Resource ID unique to localhost.
   */
  private $clientId;
  
  /**
   * @var string Resource name unique to localhost.
   */
  private $clientName;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * System unique ID.
   *
   * @return scalar Subject unique identifier.
   */
  public function getId() {
    return $this->clientId;
  }
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public function getName() {
    return $this->clientName;
  }
    
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Set unique client ID.
   *
   * @param string $clientId.
   */
  protected function setId($clientId) {
    $this->clientId = $clientId;
  }
  
  /**
   * Set client name.
   *
   * @param string $clientName.
   */
  protected function setName($clientName) {
    $this->clientName = $clientName;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->clientId = NULL;
    $this->clientName = NULL;
  }
}