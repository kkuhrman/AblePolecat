<?php
/**
 * @file      polecat/core/AccessControl/Role/Client.php
 * @brief     Client role encapsulates permission to access specific service.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource', 'Locater.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Token.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role.php')));

interface AblePolecat_AccessControl_Role_ClientInterface extends AblePolecat_AccessControl_RoleInterface {
  /**
   * Return security token granting access to service.
   *
   * @return AblePolecat_AccessControl_TokenInterface or NULL.
   */
  public function getAccessControlToken();
  
  /**
   * Return locater to service.
   *
   * @return AblePolecat_AccessControl_Resource_LocaterInterface or NULL.
   */
  public function getResourceLocater();
  
  /**
   * Set security token granting access to service.
   *
   * @param AblePolecat_AccessControl_TokenInterface $AccessControlToken.
   */
  public function setAccessControlToken(AblePolecat_AccessControl_TokenInterface $AccessControlToken);
  
  /**
   * Set locater to service.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $ResourceLocater.
   */
  public function setResourceLocater(AblePolecat_AccessControl_Resource_LocaterInterface $ResourceLocater);
}

abstract class AblePolecat_AccessControl_Role_ClientAbstract
  extends AblePolecat_AccessControl_RoleAbstract 
  implements AblePolecat_AccessControl_Role_ClientInterface {
  
  /**
   * @var AblePolecat_AccessControl_TokenInterface.
   */
  private $AccessControlToken;
  
  /**
   * @var AblePolecat_AccessControl_Resource_LocaterInterface.
   */
  private $ResourceLocater;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Role_ClientInterface.
   ********************************************************************************/
   
  /**
   * Return security token granting access to service.
   *
   * @return AblePolecat_AccessControl_TokenInterface or NULL.
   */
  public function getAccessControlToken() {
    return $this->AccessControlToken;
  }
  
  /**
   * Return locater to service.
   *
   * @return AblePolecat_AccessControl_Resource_LocaterInterface or NULL.
   */
  public function getResourceLocater() {
    return $this->ResourceLocater;
  }
  
  /**
   * Set security token granting access to service.
   *
   * @param AblePolecat_AccessControl_TokenInterface $AccessControlToken.
   */
  public function setAccessControlToken(AblePolecat_AccessControl_TokenInterface $AccessControlToken) {
    $this->AccessControlToken = $AccessControlToken;
  }
  
  /**
   * Set locater to service.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $ResourceLocater.
   */
  public function setResourceLocater(AblePolecat_AccessControl_Resource_LocaterInterface $ResourceLocater) {
    $this->ResourceLocater = $ResourceLocater;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Extends constructor.
   */
  protected function initialize() {
    parent::initialize();
    $this->AccessControlToken = NULL;
    $this->ResourceLocater = NULL;
  }
}