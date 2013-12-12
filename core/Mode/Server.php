<?php
/**
 * @file: Server.php
 * Base class for Server modes (most protected).
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Mode.php')));

class AblePolecat_Mode_Server extends AblePolecat_ModeAbstract {
  
  /**
   * Constants.
   */
  const UUID = '2621ce80-5df4-11e3-949a-0800200c9a66';
  const NAME = 'Able Polecat Server Mode';
  
  /**
   * @var AblePolecat_Mode_ServerAbstract Concrete ServerMode instance.
   */
  private static $ServerMode = NULL;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // Check for required server resources.
    // (these will throw exception if not ready).
    //
    // AblePolecat_Server::getBootMode();
    // AblePolecat_Server::getClassRegistry();
  }
  
  /**
   * Return unique, system-wide identifier.
   *
   * @return UUID.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return Common name.
   *
   * @return string Common name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // todo: Persist...
    //
    self::$ServerMode = NULL;
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_Mode_Dev or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$ServerMode)) {
      try {        
        //
        // Set access control agent
        //
        self::setAgent($Subject);
        
        switch (AblePolecat_Server::getBootDirective(AblePolecat_Server::BOOT_MODE)) {
          default:
            //
            // default mode is normal/user
            //
            require_once('Server/Normal.php');
            self::$ServerMode = new AblePolecat_Mode_Server_Normal();
            break;
          case 'install':
            require_once('Server/Install.php');
            self::$ServerMode = new AblePolecat_Mode_Server_Install();
            break;
          case 'update':
            require_once('Server/Update.php');
            self::$ServerMode = new AblePolecat_Mode_Server_Update();
            break;
        }
      }
      catch(Exception $Exception) {
        self::$ServerMode = NULL;
        throw new AblePolecat_Server_Exception(
          'Failed to initialize server mode. ' . $Exception->getMessage(),
          AblePolecat_Error::BOOT_SEQ_VIOLATION
        );
      }
    }
    return self::$ServerMode;
  }
}