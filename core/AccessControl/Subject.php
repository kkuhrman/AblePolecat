<?php
/**
 * @file      polecat/core/AccessControl/Subject.php
 * @brief     'Subject' (agent or role) seeks access to 'Object' (resource).
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */

include_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Dynamic.php')));

interface AblePolecat_AccessControl_SubjectInterface extends AblePolecat_AccessControl_Article_DynamicInterface {
}

abstract class AblePolecat_AccessControl_SubjectAbstract
  extends AblePolecat_CacheObjectAbstract 
  implements AblePolecat_AccessControl_SubjectInterface
{
  /**
   * @var int id of agent.
   */
  private $id;
  
  /**
   * @var string Name of agent.
   */
  private $name;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
  
  /**
   * Return agent id.
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
   * Default command invoker.
   *
   * @return AblePolecat_AccessControl_SubjectInterface or NULL.
   */
  protected function getDefaultCommandInvoker() {
    //
    // Agents invoke their own commands.
    //
    return $this;
  }
  
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
   * Sets the default command handlers (invoker/target).
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker
   */
  protected function setDefaultCommandInvoker(AblePolecat_AccessControl_SubjectInterface $Invoker) {
    throw new AblePolecat_AccessControl_Exception('Cannot set default command invoker for an access control subject (role or user). Access control agents invoke their own commands.');
  }
  
  /**
   * Extends __construct().
   * Sub-classes should override to initialize properties.
   */
  protected function initialize() {
    //
    // Article properties.
    //
    $this->id = NULL;
    $this->name = NULL;
  }
}