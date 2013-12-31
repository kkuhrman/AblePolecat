<?php
/**
 * @file: OAuth2.php
 * Role reserved for anonymous agent (user).
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Role', 'User', 'Authenticated.php')));

interface AblePolecat_AccessControl_Role_User_Authenticated_OAuth2Interface extends AblePolecat_AccessControl_Role_User_AuthenticatedInterface {
  
  /**
   * @return AblePolecat_AccessControl_Resource_LocaterInterface OAuth 2.0 authentication URL.
   */
  public function getAuthenticationUrl();
  
  /**
   * @return string OAuth 2.0 client secret.
   */
  public function getClientSecret();
  
  /**
   * Returns name of authenticating service provider.
   *
   * @return string Name of OAuth 2.0 authentication authority.
   */
  public function getProviderName();
  
  /**
   * @return AblePolecat_AccessControl_Resource_LocaterInterface OAuth 2.0 redirect URI.
   */
  public function getRedirectUri();
  
  /**
   * Save current session OAuth 2.0 access token to application database.
   *
   * @param object $token OAuth 2.0 access token.
   *
   * @return mixed ID of saved token otherwise FALSE.
   */
  public function saveToken($token);
  
  /**
   * Retrieve current session OAuth 2.0 access token from application database.
   *
   * @return object $token OAuth 2.0 access token.
   */
  public function loadToken();
  
  /**
   * Delete current session OAuth 2.0 access token from application database.
   */
  public function deleteToken();
}

abstract class AblePolecat_AccessControl_Role_User_Authenticated_OAuth2Abstract extends AblePolecat_CacheObjectAbstract implements AblePolecat_AccessControl_Role_User_Authenticated_OAuth2Interface {
  
  /**
   * @var object OAuth 2.0 token.
   */
  private $token;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    $this->token = NULL;
  }
  
  /**
   * Save current session OAuth 2.0 access token to application database.
   *
   * @param object $token OAuth 2.0 access token.
   *
   * @return bool TRUE if token was saved otherwise FALSE.
   */
  public function saveToken($token) {
    
    $result = FALSE;
    
    try {
      if (isset($token)) {
        $interface = get_class($this);
        $session_id = AblePolecat_Server::getAccessControl()->getSession()->getId();
        $session_data = serialize($token);    
        $Database = AblePolecat_Server::getDatabase();
        $sql = NULL;
        
        if (!isset($this->token)) {
          $this->token = $token;
          $sql = __SQL()->
            insert('session_id', 'interface', 'session_data')->
            into ('role')->
            values($session_id, $interface, $session_data);
        }
        else if ($this->token !== $token) {
          //
          // token was updated lazily
          //
          $this->token = $token;
          $sql = __SQL()->
            update('role')->
            set('session_data')->
            values($session_data)->
            where(__SQLEXPR('session_id', '=', $session_id), 'AND', __SQLEXPR('interface', '=', $interface));
        }
        
        if (isset($sql)) {
          $PreparedStatement = $Database->prepareStatement($sql);
          $result = $PreparedStatement->execute();
          if (!$result) {
            $this->token = NULL;
            $Database->logErrorInfo();
          }
        }
      }
    }
    catch (AblePolecat_Exception $Exception) {
      AblePolecat_Command_Log::invoke($this, $Exception->getMessage(), AblePolecat_LogInterface::WARNING);
    }
    
    return $result;
  }
  
  /**
   * Retrieve current session OAuth 2.0 access token from application database.
   *
   * @return object $token OAuth 2.0 access token or NULL.
   */
  public function loadToken() {
    
    try {
      $interface = get_class($this);
      $session_id = AblePolecat_Server::getAccessControl()->getSession()->getId();
      // $session_data = serialize($this->token);    
      $Database = AblePolecat_Server::getDatabase();
      $sql = __SQL()->
        select('session_data')->
        from('role')->
        where(__SQLEXPR('session_id', '=', $session_id), 'AND', __SQLEXPR('interface', '=', $interface));
      $PreparedStatement = $Database->prepareStatement($sql);
      $result = $PreparedStatement->execute();
      if ($result) {
        $data = $PreparedStatement->fetch();
        isset($data['session_data']) ? $this->token = unserialize($data['session_data']) : $this->token = NULL;
      }
      else {
        $this->token = NULL;
        $Database->logErrorInfo();
      }
    }
    catch (AblePolecat_Exception $Exception) {
      $this->token = NULL;
      AblePolecat_Command_Log::invoke($this, $Exception->getMessage(), AblePolecat_LogInterface::WARNING);
    }
    
    return $this->token;
  }
  
  /**
   * Delete current session OAuth 2.0 access token from application database.
   */
  public function deleteToken() {
    
    try {
      $interface = get_class($this);
      $session_id = AblePolecat_Server::getAccessControl()->getSession()->getId();
      $Database = AblePolecat_Server::getDatabase();
      $sql = __SQL()->
        delete()->
        from('role')->
        where(__SQLEXPR('session_id', '=', $session_id), 'AND', __SQLEXPR('interface', '=', $interface));
      $PreparedStatement = $Database->prepareStatement($sql);
      $result = $PreparedStatement->execute();
      if (!$result) {
        $Database->logErrorInfo();
      }
      $this->token = NULL;
    }
    catch (AblePolecat_Exception $Exception) {
      $this->token = NULL;
      AblePolecat_Command_Log::invoke($this, $Exception->getMessage(), AblePolecat_LogInterface::WARNING);
    }
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    $this->saveToken($this->token);
  }
}
