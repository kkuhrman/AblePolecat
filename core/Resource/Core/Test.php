<?php
/**
 * @file      AblePolecat/core/Resource/Core/Test.php
 * @brief     Encapsulates results of local unit tests.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR , array(ABLE_POLECAT_CORE, 'Resource', 'Core.php')));

class AblePolecat_Resource_Core_Test extends AblePolecat_Resource_CoreAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource;
  
  /**
   * @var Array Results of test(s).
   */
  private $testResults;
  
  /**
   * Constants.
   */
  const UUID = '912f21ea-df92-11e4-b585-0050569e00a2';
  const NAME = AblePolecat_Message_RequestInterface::RESOURCE_NAME_TEST;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_Resource_Core_Test
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Core_Test();
    }
    return self::$Resource;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Dom_NodeInterface.
   ********************************************************************************/
   
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL) {
    
    $Element = NULL;
    
    //
    // Create a default document if necessary.
    //
    !isset($Document) ? $Document = AblePolecat_Dom::createXmlDocument() : NULL;
    
    //
    // Create root element.
    //
    $Element = $Document->createElement('testResultSets');
    $Element = AblePolecat_Dom::appendChildToParent($Element, $Document);
    // AblePolecat_Debug::kill($Element);
    //
    // Add child elements for properties.
    //
    foreach($this->testResults as $className => $ResultSet) {
      $resultSetElement = $Document->createElement('testResultSet');
      $resultSetElement->setAttribute('className', $className);
      $Element->appendChild($resultSetElement);
      // $resultSetElement = AblePolecat_Dom::appendChildToParent($resultSetElement, $Document, $Element);
      foreach($ResultSet as $key => $Result) {
        $resultElement = $Result->getDomNode($Document);
        $resultSetElement->appendChild($resultElement);
      }
    }
    // while($property) {
      // $tagName = $this->getPropertyKey();
      // $childElement = $Document->createElement($tagName);
      // if (is_a($property, 'AblePolecat_Data_Primitive_ScalarInterface')) {
        // $cData = $Document->createCDATASection($property->__toString());
        // $childElement->appendChild($cData);
      // }
      // else {
        // $childNode = $property->getDomNode($Document);
        // $childElement->appendChild($childNode);
      // }
      // $childElement = AblePolecat_Dom::appendChildToParent($childElement, $Document);
      // $property = $this->getNextProperty();
    // }
    
    return $Element;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Set results of a unit test.
   *
   * @param AblePolecat_UnitTest_ResultInterface $Result.
   */
  public function setTestResult(AblePolecat_UnitTest_ResultInterface $Result) {
    $className = $Result->getClassName();
    $functionName = $Result->getFunctionName();
    if (isset($className)) {
      $className = $className->__toString();
      if (!isset($this->testResults[$className])) {
        $this->testResults[$className] = array();
      }
      $functionName = $functionName->__toString(); 
      $this->testResults[$className][$functionName] = $Result;
    }
    else {
      //
      // @todo: trigger error or throw exception?
      //
    }
  }
  
  /**
   * Validates request URI path to ensure resource request can be fulfilled.
   *
   * @throw AblePolecat_Exception If request URI path is not validated.
   */
  protected function validateRequestPath() {
    //
    // Request path is irrelevant in this case.
    //
  }
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    parent::initialize();
    $this->setId(self::UUID);
    $this->setName(self::NAME);
    $this->testResults = array();
  }
}