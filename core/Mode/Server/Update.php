<?php
/**
 * @file: Qa.php
 * Boots Able Polecat server in test (quality assurance) mode.
 */

class AblePolecat_Mode_Server_Update extends AblePolecat_Mode_Server {
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {

    parent::initialize();

    set_error_handler(array('AblePolecat_Mode_Server', 'defaultErrorHandler'));
    set_exception_handler(array('AblePolecat_Mode_Server', 'defaultExceptionHandler'));
    
    //
    // Load environment/configuration
    //
    $this->setEnvironment(AblePolecat_Environment_Server::wakeup(self::getAgent()));
  }
}