<?php
/**
 * @file      AblePolecat/core/Transaction/Test.php
 * @brief     Built-in transaction manages local unit test execution.
 *
 * Any request for host/test/... will be directed here. The part of the URI path,
 * which follows ./test is expected to be a relative path off the test directory,
 * which is root/usr/share/test by convention. The name of the test class is 
 * expected to be [LibraryPrefix]Test_[NameOfClass]. As an example, AblePolecat 
 * is the library prefix for the core class library. A test class for the core 
 * class AblePolecat_Data_Primitive_Scalar_String is expected to bear the name 
 * AblePolecatTest_Data_Primitive_Scalar_String and would be located in the 
 * file AblePolecat/usr/share/test/Data/Primitive/Scalar/String.php. So the 
 * request to run all tests for this class would be host/test/data/primitive/scalar/string
 * (Able Polecat treats URI paths as case insensitive). If we are running one 
 * or more specific test add /?test=testName1+testName2... to the query string 
 * and if we are running a test for a class in a library other than the core 
 * (and there is no override to getTestDirective()) one must pass the library
 * name or id in the query string as well like so ./?lib=[libName | libId].
 *
 * In the case where a class name contains a mixed case word, such as 
 * AblePolecat_AccessControl_Agent_User (where 'AccessControl' is the mixed 
 * case word), the parts of the word should be separated by a hyphen in the 
 * request URI as in ./test/access-control/agent/user. The same goes for 
 * method. Library ids and names, on the other hand, are likely to contain 
 * hyphens and so the syntax for breaking up words separated by hyphens is 
 * the same as passing multiple values for a parameter. For example, the library
 * name AblePolecat-Dev would be passed like so ./?lib=able-polecat+dev and the
 * library id 6f7bd8bb-e1f5-11e4-b585-0050569e00a2 like so 6f7bd8bb+e1f5+11e4+b585+0050569e00a2.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Transaction.php')));

class AblePolecat_Transaction_Test extends  AblePolecat_TransactionAbstract {
  
  /**
   * Registry article constants.
   */
  const UUID = '3c421fbe-df90-11e4-b585-0050569e00a2';
  const NAME = 'AblePolecat_Transaction_Test';
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface Session status helps determine if connection is new or established.
   *
   * @return AblePolecat_CacheObjectInterface Initialized server resource ready for business or NULL.
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    $Transaction = new AblePolecat_Transaction_Test($ArgsList->getArgumentValue(self::TX_ARG_SUBJECT));
    self::prepare($Transaction, $ArgsList, __FUNCTION__);
    return $Transaction;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_TransactionInterface.
   ********************************************************************************/
  
  /**
   * Commit
   */
  public function commit() {
  }
  
  /**
   * Rollback
   */
  public function rollback() {
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
    // Unit tests can only be executed by local host.
    //
    isset($_SERVER['REMOTE_ADDR']) ? $remoteIp = $_SERVER['REMOTE_ADDR'] : $remoteIp = 'unknown';
    if ($remoteIp === '127.0.0.1') {
      //
      // Check request method.
      //
      $method = $this->getRequest()->getMethod();
      switch ($method) {
        default:
          break;
        case 'GET':
          //
          // Extract name of unit test(s) from URI path & query string.
          //
          if ($this->getTestDirective()) {
            //
            // Select unit test(s).
            //
            $Resource = $this->selectTests();
          }
          else {
            $Resource = $this->executeTests();
          }
          break;
        case 'POST':
          //
          // Execute unit test(s) and return results.
          //
          $Resource = $this->executeTests();
          break;
      }
    }
    else {
      $Resource = AblePolecat_Resource_Core_Factory::wakeup(
        $this->getDefaultCommandInvoker(),
        'AblePolecat_Resource_Core_Error',
        'Forbidden',
        sprintf("Unit tests can only be executed by local host. Your address is %s.", $remoteIp)
      );
      $this->setStatusCode(403);
      $this->setStatus(self::TX_STATE_COMPLETED);
    }
    return $Resource;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /** 
   * Extract name of test(s) to run from URI path and query string.
   */
  public function getTestDirective() {
    
    $testDirectiveSet = FALSE;
    
    //
    // Check query string for lib directive.
    //
    $libFullPath = NULL;
    $queryString = $this->getRequest()->getRequestQueryString(FALSE);
    $libPrefix = '';
    if (isset($queryString['lib']) && isset($queryString['lib'][0])) {
      $libParm = $queryString['lib'][0];
      $libVal = $this->transformRequestPathPart($libParm, 'lib');
      
      //
      // Assumption is that user will pass library name before id but either 
      // will be looked for.
      //
      $sql = __SQL()->          
        select(
          'id', 
          'name', 
          'libType', 
          'classPrefix', 
          'libFullPath', 
          'useLib', 
          'lastModifiedTime')->
        from('lib')->
        where(sprintf("`name` = '%s'", $libVal));
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success()) {
        $registrationInfo = $CommandResult->value();
        if (isset($registrationInfo[0])) {
          $RegistryEntry = AblePolecat_Registry_Entry_ClassLibrary::create($registrationInfo[0]);
          $libPrefix = $RegistryEntry->getClassPrefix();
          if (($RegistryEntry->getClassLibraryType() == 'app') ||
              ($RegistryEntry->getClassLibraryType() == 'mod')) {
            $libFullPath = dirname(dirname($RegistryEntry->getClassLibraryFullPath()));
          }
          else {
            if ($RegistryEntry->getName() == 'AblePolecat') {
              $libFullPath = dirname($RegistryEntry->getClassLibraryFullPath());
            }
          }
        }
      }
    }
    $libTestPath = implode(DIRECTORY_SEPARATOR, array($libFullPath, 'usr', 'share', 'test'));
    
    //
    // Check URI path for a valid test class.
    //
    $requestPathParts = $this->getRequest()->getRequestPath(FALSE);
    $requestPathPartsTransform = array($libTestPath);
    foreach($requestPathParts as $key => $pathPart) {
      if ($key == 0) {
        //
        // discard test directive.
        //
      }
      else {
        $requestPathPartsTransform[] = $this->transformRequestPathPart($pathPart);
      }
    }
    $testClassPath = sprintf("%s.php",
      implode(DIRECTORY_SEPARATOR, $requestPathPartsTransform)
    );
    if (!AblePolecat_Server_Paths::verifyFile($testClassPath)) {
      //
      // The file does not exist
      //
      throw new AblePolecat_Transaction_Exception("Invalid path for test class. No such file exists: $testClassPath");
    }
    
    //
    // File exists, so include it and build the test class name (by convention).
    //
    include_once($testClassPath);
    $testPath = array_shift($requestPathPartsTransform);
    $testClassName = sprintf("%sTest_%s", $libPrefix, implode('_', $requestPathPartsTransform));
    // AblePolecat_Debug::kill($testClassName);
    // $testClassName::runTests();
    die('@todo: check if we are running all tests or only certain methods.');
    // else {
      // 
    // }
    
    return $testDirectiveSet;
  }
  
  /**
   * Return a form for selecting one or more unit tests to run.
   *
   * @return AblePolecat_ResourceInterface The result of the work, partial or completed.
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  protected function selectTests() {
    $Resource = AblePolecat_Resource_Core_Factory::wakeup(
      $this->getAgent(),
      'AblePolecat_Resource_Core_Form'
    );
    // $Resource->addText('Enter database name, user name and password for Able Polecat core database.');
    // $Resource->addControl('label', array('for' => 'databaseName'), 'Database: ');
    // $Resource->addControl('input', array('id' => 'databaseName', 'type' => 'text', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_DB));
    // $Resource->addControl('label', array('for' => 'userName'), 'Username: ');
    // $Resource->addControl('input', array('id' => 'userName', 'type' => 'text', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_USER));
    // $Resource->addControl('label', array('for' => 'passWord'), 'Password: ');
    // $Resource->addControl('input', array('id' => 'passWord', 'type' => 'password', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_PASS));
    // $Resource->addControl('input', array('type'=>'hidden', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_REDIRECT, 'value' => AblePolecat_Resource_Core_Test::UUID));
    // $Resource->addControl('input', array('type'=>'hidden', 'name' => AblePolecat_Transaction_RestrictedInterface::ARG_REFERER, 'value' => AblePolecat_Resource_Core_Test::UUID));
    // return $Resource;
  }
  
  /**
   * Execute unit test(s) and return results.
   *
   * @return AblePolecat_ResourceInterface The result of the work, partial or completed.
   * @throw AblePolecat_Transaction_Exception If cannot be brought to a satisfactory state.
   */
  protected function executeTests() {
    $Resource = AblePolecat_Resource_Core_Factory::wakeup(
      $this->getAgent(),
      'AblePolecat_Resource_Core_Test'
    );
    return $Resource;
  }
  
  /**
   * Format test class path/name part by convention.
   *
   * @param string $pathPart
   * @param string $transformation path | class | lib | method
   *
   * @return string.
   */
  public function transformRequestPathPart($pathPart, $transformation = 'class') {
    
    $transform = '';
    
    switch ($transformation) {
      default:
        break;
      case 'lib':
      case 'path':
        //
        // library ids and names and directory and file names can contain hyphens.
        //
        $words = explode(' ', $pathPart);
        $transformParts = array();
        foreach($words as $key => $word) {
          $transformParts[] = str_replace(' ', '', ucwords(strtolower(str_replace('-', ' ', trim($word)))));
        }
        $transform = implode('-', $transformParts);
        break;
      case 'class':
      case 'method':
        $transform = str_replace(' ', '', ucwords(strtolower(str_replace('-', ' ', trim($pathPart)))));
        break;
    }
    return $transform;
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
  }
}