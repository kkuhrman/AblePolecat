<?php
/**
 * @file: AccessControl.php
 * Public interfaces to essential Able Polecat access control objects.
 */

include_once(ABLE_POLECAT_PATH . DIRECTORY_SEPARATOR . 'Exception.php');

/**
 * Similar to 'the' (definite), 'a'/'an' (indefinite) in English grammar.
 * Used to  indicate the type of reference (general, specific, etc) being 
 * made by the Subject, Object, Constraint etc. in an access control system.
 */
interface AblePolecat_AccessControl_ArticleInterface {
  
  /**
   * Ideally unique id will be UUID.
   *
   * @return string Subject unique identifier.
   */
  public static function getId();
  
  /**
   * Common name, need not be unique.
   *
   * @return string Common name.
   */
  public static function getName();
}
 
/**
 * 'Subject' (agent or role) seeks access to 'Object' (resource).
 */
interface AblePolecat_AccessControl_SubjectInterface extends AblePolecat_AccessControl_ArticleInterface {
}

/**
 * The opposite, denial of a permission; e.g. deny_write = TRUE.
 */
interface AblePolecat_AccessControl_ConstraintInterface extends AblePolecat_AccessControl_ArticleInterface {
}

/**
 * The access control 'subject'; for example, a user.
 *
 * Intended to follow interface specified by W3C but does not provide public access to 
 * properties (get/set methods provided).
 *
 * @see http://www.w3.org/TR/url/#url
 */
interface AblePolecat_AccessControl_AgentInterface extends AblePolecat_AccessControl_SubjectInterface {
  
  /**
   * Assign the given role to the agent.
   *
   * @param object Object implementing AblePolecat_AccessControl_RoleInterface.
   *
   * @return bool TRUE if the role is assigned to agent, otherwise FALSE.
   */
  public function assignRole(AblePolecat_AccessControl_RoleInterface $Role);
  
  /**
   * Return roles assigned to agent.
   *
   * @return Array Zero or more instances of class implementing AblePolecat_AccessControl_RoleInterface.
   */
  public function getRoles();
  
  /**
   * Creational function, load agent from storage with no active session.
   *
   * @return object Instance of class implmenting AblePolecat_AccessControl_AgentInterface.
   */
  public static function load();
  
  /**
   * Creational function, load agent from storage and resume session.
   *
   * @return object Instance of class implmenting AblePolecat_AccessControl_AgentInterface.
   */
  public static function wakeup();
}

/**
 * A job function within the system such as 'anonymous', 'authenticated', 'administrator' etc.
 */
interface AblePolecat_AccessControl_RoleInterface extends AblePolecat_AccessControl_SubjectInterface {

  /**
   * Specify if role is authorized for given agent object.
   *
   * @param object Object implementing AblePolecat_AccessControl_AgentInterface.
   *
   * @return bool TRUE if role is authorized for given agent object, otherwise FALSE.
   */
  public static function isAuthorized(AblePolecat_AccessControl_AgentInterface $Agent);
  
  /**
   * Creational function, initialize members from storage.
   *
   * @return object Instance of class which implments AblePolecat_AccessControl_RoleInterface.
   */
  public static function load();
}

/**
 * The URL part of a URI.
 */
interface AblePolecat_AccessControl_Resource_LocaterInterface {
  
  /**
   * Create URL.
   * 
   * @param DOMString $url Relative or absolute path.
   * @param optional DOMString $baseURL.
   *
   * @return object Instance of class implementing AblePolecat_AccessControl_Resource_LocaterInterface or NULL.
   */
  public static function create($url, $baseURL = NULL);
  
  /**
   * @return DOMString protocol.
   */
  public function getProtocol();
  
  /**
   * @return DOMString username.
   */
  public function getUsername();
  
  /**
   * @return DOMString password.
   */
  public function getPassword();
  
  /**
   * @return DOMString host.
   */
  public function getHost();
  
  /**
   * @return DOMString hostname.
   */
  public function getHostname();
  
  /**
   * @return DOMString port.
   */
  public function getPort();
  
  /**
   * @return DOMString pathname.
   */
  public function getPathname();
  
  /**
   * @return DOMString search.
   */
  public function getSearch();
  
  /**
   * @return DOMString hash.
   */
  public function getHash();
  
  /**
   * @return DOMString filename.
   */
  public function getFilename();
  
  /**
   * @return DOMString origin.
   */
  public function getOrigin();
  
  /**
   * Set protocol.
   *
   * @param DOMString $protocol
   */
  public function setProtocol($protocol);
  
  /**
   * Set username.
   *
   * @param DOMString $username
   */
  public function setUsername($username);
  
  /**
   * Set password.
   *
   * @param DOMString $password
   */
  public function setPassword($password);
  
  /**
   * Set host.
   *
   * @param DOMString $host
   */
  public function setHost($host);
  
  /**
   * Set hostname.
   *
   * @param DOMString $hostname
   */
  public function setHostname($hostname);
  
  /**
   * Set port.
   *
   * @param DOMString $port
   */
  public function setPort($port);
  
  /**
   * Set pathname.
   *
   * @param DOMString $pathname
   */
  public function setPathname($pathname);
  
  /**
   * Set search.
   *
   * @param DOMString $search
   */
  public function setSearch($search);
  
  /**
   * Set hash.
   *
   * @param DOMString $hash
   */
  public function setHash($hash);
  
  /**
   * Set filename.
   *
   * @param DOMString $filename
   */
  public function setFilename($filename);
  
  /**
   * Return all unique names of parameters in list.
   *
   * @return Array Names of parameters.
   */
  public function getParameterNames();
  
  /**
   * Return all values for parameter by given name.
   * 
   * @param DOMString $name Name of given parameter.
   *
   * @return Array All parameter values or NULL.
   */
  public function getParameterValues($name);
  
  /**
   * @return bool TRUE if given parameter set, otherwise FALSE.
   */
  public function hasParameter($name);
  
  /**
   * Get value of given parameter.
   *
   * @param DOMString $name Name of parameter to update.
   *
   * @return DOMString Value of parameter or NULL.
   */
   public function getParameter($name);
   
  /**
   * Set value of given parameter.
   *
   * @param DOMString $name Name of parameter to update.
   * @param DOMString $value Parameter value.
   */
  public function setParameter($name, $value);
  
  /**
   * Add given parameter to list.
   *
   * @param DOMString $name Name of parameter to add to list.
   * @param DOMString $value Parameter value.
   */
  public function addParameter($name, $value);
  
  /**
   * Remove given parameter from list.
   *
   * @param DOMString $name Name of parameter to remove.
   */
  public function removeParameter($name);
  
  /**
   * Clear all parameters, reset list.
   */
  public function clearParameters();
  
  /**
   * Return URL as a string.
   *
   * @return DOMString href.
   */
  public function __toString();
}

/**
 * The access control 'object', some resource secured by constraints which agents may 
 * seek to gain access to; e.g. a file, a device, a database connection, etc.
 */
interface AblePolecat_AccessControl_ResourceInterface extends AblePolecat_AccessControl_ArticleInterface {
  
  /**
   * Sets given constraint on the resource.
   *
   * By default, setting constraint on this resource denies this action to all 
   * agents and roles. 
   *
   * @param AblePolecat_AccessControl_ConstraintInterface $Constraint.
   *
   * @return bool TRUE if constraint is set, otherwise FALSE.
   * 
   * @see setPermission().
   */
  public function setConstraint(AblePolecat_AccessControl_ConstraintInterface $Constraint);
  
  /**
   * Sets permission for given agent or role.
   *
   * In actuality, unless a constraint is set on the resource, all agents and roles 
   * have permission for corresponding action. If constraint is set, setPermission 
   * simply exempts agent or role from that constraint (i.e. 'unblocks' them).
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Agent or role.
   * @param string $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   *
   * @return bool TRUE if permission is granted, otherwise FALSE.
   */
  public function setPermission(AblePolecat_AccessControl_SubjectInterface $Subject, $constraint_id);
  
  /**
   * Verifies that agent or role has given permission.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject Agent or role.
   * @param string $constraint_id i.e. AblePolecat_AccessControl_ConstraintInterface::getId().
   *
   * @return bool TRUE if agent or role has permission, otherwise FALSE.
   */
  public function hasPermission(AblePolecat_AccessControl_SubjectInterface $Subject, $constraint_id);
  
  /**
   * Creational function; similar to UNIX program, creates an empty resource.
   *
   * @return object Instance of class which implments AblePolecat_AccessControl_ResourceInterface.
   */
  public static function touch();
  
  /**
   * Opens an existing resource or makes an empty one accessible depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking access.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Existing or new resource.
   * @param string $name Optional common name for new resources.
   *
   * @return bool TRUE if access to resource is granted, otherwise FALSE.
   */
  public function open(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_AccessControl_Resource_LocaterInterface $Url = NULL);
  
  /**
   * Read from an existing resource or depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking to read.
   * @param string $start Optional offset to start reading from.
   * @param string $end Optional offset to end reading at.
   *
   * @return mixed Data read from resource or NULL.
   */
  public function read(AblePolecat_AccessControl_AgentInterface $Agent, $start = NULL, $end = NULL);
  
  /**
   * Write to a new or existing resource or depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking to read.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Existing or new resource.
   *
   * @return bool TRUE if write to resource is successful, otherwise FALSE.
   */
  public function write(AblePolecat_AccessControl_AgentInterface $Agent, AblePolecat_AccessControl_Resource_LocaterInterface $Url);
}

/**
 * Exceptions thrown by Able Polecat Access Control objects.
 */
class AblePolecat_AccessControl_Exception extends AblePolecat_Exception {
  /**
   * Error codes for access control.
   */
  const ERROR_ACCESS_DENIED             = 0x00000001; // Catch-all, non-specific access denied error.
  const ERROR_ACCESS_ROLE_NOT_AUTH      = 0x00000010; // Agent could not be assigned to given role.
  const ERROR_ACCESS_ROLE_DENIED        = 0x00000020; // Role denied access to resource.
}
