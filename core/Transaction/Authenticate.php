<?php
/**
 * @file      polecat/core/Transaction/Authenticate.php
 * @brief     Base class for transactions, which require user authentication.
 * 
 * Primary function of this class is to retrieve and verify user authentication 
 * by reading credentials from a local project configuration file or processing
 * login results passed in the entity body of a POST request (e.g. user name and
 * password; or OAuth 2.0 security token etc).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction.php')));

interface AblePolecat_Transaction_AuthenticateInterface extends AblePolecat_TransactionInterface  {
  /**
   * @return boolean TRUE if user authentication is valid, otherwise FALSE.
   */
  public function authenticate();
  
  /**
   * Return access control agent role authorizing resource access.
   *
   * @return AblePolecat_AccessControl_RoleInterface.
   */
  public function getAccessControlRole();
  
  /**
   * Return locater of resource, which will authenticate user.
   *
   * @return AblePolecat_AccessControl_Resource_LocaterInterface.
   */
  public function getAccessControlResourceLocater();
  
  /**
   * Return locater of requested resource.
   *
   * @return AblePolecat_AccessControl_Resource_LocaterInterface.
   */
  public function getRequestedResourceLocater();
}

abstract class AblePolecat_Transaction_AuthenticateAbstract 
  extends AblePolecat_TransactionAbstract
  implements AblePolecat_Transaction_AuthenticateInterface {
  
  /**
   * @var boolean User authentication status.
   */
  private $authenticated;
  
  /**
   * @var AblePolecat_AccessControl_RoleInterface.
   */
  private $AccessControlRole;
  
  /**
   * @var AblePolecat_AccessControl_Resource_LocaterInterface.
   */
  private $AccessControlResourceLocater;
  
  /**
   * @var AblePolecat_AccessControl_Resource_LocaterInterface.
   */
  private $RequestedResourceLocater;
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
   
  /**
   * Commit
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
   * Begin or resume the transaction.
   *
   * @return AblePolecat_ResourceInterface The result of the work, partial or completed.
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function start() {
    
    $Resource = NULL;
    
    //
    // If user is authenticated, return requested resource.
    //
    if ($this->authenticate()) {
      
    }
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Transaction_AuthenticateInterface.
   ********************************************************************************/
   
  /**
   * @return boolean TRUE if user authentication is valid, otherwise FALSE.
   */
  public function authenticate() {
    return $this->authenticated;
  }
  
  /**
   * Return access control agent role authorizing resource access.
   *
   * @return AblePolecat_AccessControl_RoleInterface.
   */
  public function getAccessControlRole() {
    return $this->AccessControlRole;
  }
  
  /**
   * Return locater of resource, which will authenticate user.
   *
   * @return AblePolecat_AccessControl_Resource_LocaterInterface.
   */
  public function getAccessControlResourceLocater() {
    return $this->AccessControlResourceLocater;
  }
  
  /**
   * Return locater of requested resource.
   *
   * @return AblePolecat_AccessControl_Resource_LocaterInterface.
   */
  public function getRequestedResourceLocater() {
    return $this->RequestedResourceLocater;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Run the install procedures.
   *
   * @return AblePolecat_ResourceInterface
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  public function run() {
    
    $Resource = NULL;
    
    return $Resource;
  }
  
  /**
   * Set user authentication status.
   *
   * @param boolean TRUE $authenticated.
   */
  protected function setAuthenticationStatus($authenticated) {
    $this->authenticated = $authenticated;
  }
  
  /**
   * Set access control agent role authorizing resource access.
   *
   * @param AblePolecat_AccessControl_RoleInterface $Role.
   */
  protected function setAccessControlRole(AblePolecat_AccessControl_RoleInterface $Role) {
    $this->AccessControlRole = $Role;
  }
  
  /**
   * Set locater of resource, which will authenticate user.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Locater.
   */
  protected function setAccessControlResourceLocater(AblePolecat_AccessControl_Resource_LocaterInterface $Locater) {
    $this->AccessControlResourceLocater = $Locater;
  }
  
  /**
   * Set locater of requested resource.
   *
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Locater.
   */
  protected function setRequestedResourceLocater(AblePolecat_AccessControl_Resource_LocaterInterface $Locater) {
    $this->RequestedResourceLocater = $Locater;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->authenticated = FALSE;
    $this->AccessControlRole = NULL;
    $this->AccessControlResourceLocater = NULL;
    $this->RequestedResourceLocater = NULL;
  }
}