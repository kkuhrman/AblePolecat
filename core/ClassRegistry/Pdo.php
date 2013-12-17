<?php
/**
 * @file: Pdo.php
 * Stores class registry in application database.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'ClassRegistry.php')));

class AblePolecat_ClassRegistry_Pdo extends AblePolecat_ClassRegistryAbstract {
  
  /**
   * @var AblePolecat_ClassRegistry Singleton instance.
   */
  private static $ClassRegistry = NULL;
  
  /**
   * @var AblePolecat_Database_Pdo Connection to application database.
   */
  private $Database;
  
  /**
   * Extends constructor.
   * Sub-classes should override to initialize members.
   */
  protected function initialize() {
    
    parent::initialize();
    
    //
    // Check db connection
    //
    $this->Database = AblePolecat_Server::getDatabase(FALSE);
    if (!isset($this->Database)) {
      throw new AblePolecat_ClassRegistry_Exception('Failed to wakeup class registry because there is no active connection to the application database.', 
        AblePolecat_Error::DB_NO_CONNECTION
      );
    }

    //
    // Populate class from application database
    // Query application database for registered classes.
    //
    $sql = __SQL()->          
      select('name', 'path', 'method')->
      from('class');
    $Stmt = $this->Database->prepareStatement($sql);
    if ($Stmt->execute()) {
      while ($result = $Stmt->fetch()) {
        $this->registerLoadableClass($result['name'], $result['path'], $result['method']);
      }
    }
  }
  
  /**
   * Serialize object to cache.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject.
   */
  public function sleep(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    try {
      //
      // @todo: install/update mode only
      //
      // if (isset($this->Database)) {
      if ((AblePolecat_Server::getBootMode() !== AblePolecat_Server::BOOT_MODE_NORMAL) && isset($this->Database)) {
        $registeredClasses = $this->getRegisteredClasses();
        foreach($registeredClasses as $class_name => $class_info) {
          $result = FALSE;
          isset($class_info[self::CLASS_REG_PATH]) ? $path = $class_info[self::CLASS_REG_PATH] : $path = NULL; 
          isset($class_info[self::CLASS_REG_METHOD]) ? $method = $class_info[self::CLASS_REG_METHOD] : $method = NULL;
          if (isset($path) && isset($method)) {
            //
            // Insert new or update existing entry
            // @todo: prid, scope
            //
            $sql = __SQL()->          
              replace('name', 'path', 'method')->
              into('class')->
              values($class_name, $path, $method);
            $Stmt = $this->Database->prepareStatement($sql);
            $result = $Stmt->execute();
          }
          if (!$result) {
            AblePolecat_Server::log(AblePolecat_LogInterface::WARNING, "Failed to save $class_name registry");
          }
        }
      }
    }
    catch (Exception $Exception) {
      AblePolecat_Server::log(AblePolecat_LogInterface::WARNING, 'Failed to persist class registry. ' . $Exception->getMessage());
    }
  }
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    if (!isset(self::$ClassRegistry)) {
      try {
        self::$ClassRegistry = new AblePolecat_ClassRegistry_Pdo();
      }
      catch (Exception $Exception) {
        self::$ClassRegistry = NULL;
        throw new AblePolecat_ClassRegistry_Exception($Exception->getMessage(), AblePolecat_Error::WAKEUP_FAIL);
      }
    }
    return self::$ClassRegistry;
  }
}