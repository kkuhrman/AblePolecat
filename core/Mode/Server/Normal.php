<?php
/**
 * @file: Normal.php
 * Boots Able Polecat server in normal mode.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Environment', 'Server.php')));

class AblePolecat_Mode_Server_Normal extends AblePolecat_Mode_Server {
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // Default error/exception handling
    //
    set_error_handler(array('AblePolecat_Mode_Server', 'defaultErrorHandler'));
    set_exception_handler(array('AblePolecat_Mode_Server', 'defaultExceptionHandler'));
    
    //
    // Load environment/configuration
    //
    //
    $Environment = AblePolecat_Environment_Server::wakeup($this->getAgent());
    
    // Set access control constraints.
    //
    $Environment->setPermission($this->getAgent(), AblePolecat_AccessControl_Constraint_Read::getId());
    $Environment->setPermission($this->getAgent(), AblePolecat_AccessControl_Constraint_Write::getId());
    
    //
    // Save 
    //
    $this->setEnvironment($Environment);
  }
}