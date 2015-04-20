<?php
/**
 * @file      AblePolecat/core/UnitTest/Result.php
 * @brief     Encapsulates the results expected on success of a unit test.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Exception', 'UnitTest.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Dom', 'Node.php')));

interface AblePolecat_UnitTest_ResultInterface extends AblePolecat_Dom_NodeInterface {
  /** 
   * @return AblePolecat_Data_Primitive_Scalar_String Name of class subject of test.
   */
  public function getClassName();
  
  /**
   * Returns name of expected result data type.
   *
   * Should be the same as passing return value to AblePolecat_Data::getDataTypeName().
   * Either the name of a primitive data type or a class name.
   *
   * @return AblePolecat_Data_Primitive_Scalar_String.
   */
  public function getDataTypeName(); 
  
  /**
   * @return AblePolecat_Data_Primitive_Scalar_String Name of function subject of test.
   */
  public function getFunctionName();
  
  /**
   * @return AblePolecat_Data_Primitive_Scalar_String Message explaining why test failed.
   */
  public function getMessage();
  
  /**
   * @return AblePolecat_Data_Primitive_Scalar_Boolean TRUE if test passed otherwise FALSE.
   */
  public function getTestResult();
  
  /**
   * @return AblePolecat_Data_Primitive_Scalar_Boolean TRUE if result is OUTPUT of a test, otherwise FALSE.
   */
  public function isOutput();
  
  /** 
   * @param string $className.
   */
  public function setClassName($className);
  
  /**
   * Set name of expected result data type.
   *
   * @param string $returnDataType.
   */
  public function setDataTypeName($returnDataType);
  
  /** 
   * @param string $functionName.
   */
  public function setFunctionName($functionName);
  
  /**
   * Set test result to fail.
   *
   * @param AblePolecat_UnitTest_Exception Exception thrown by test method.
   */
  public function setFail(AblePolecat_UnitTest_Exception $Exception);
  
  /**
   * Set test result to pass.
   */
  public function setPass();
}

class AblePolecat_UnitTest_Result implements AblePolecat_UnitTest_ResultInterface {
  
  /**
   * @var AblePolecat_Data_Primitive_Scalar_String.
   */
  private $className;
  
  /**
   * @var AblePolecat_Data_Primitive_Scalar_String.
   */
  private $functionName;
  
  /**
   * @var AblePolecat_Data_Primitive_Scalar_Boolean.
   */
  private $isOutput;
  
  /**
   * @var AblePolecat_Data_Primitive_Scalar_String.
   */
  private $message;
  
  /**
   * @var AblePolecat_Data_Primitive_Scalar_Boolean.
   */
  private $testResult;
  
  /**
   * @var string.
   */
  private $returnDataType;
  
  /********************************************************************************
   * Implementation of AblePolecat_Dom_NodeInterface
   ********************************************************************************/
  
  /**
   * @param DOMDocument $Document.
   *
   * @return DOMElement Encapsulated data expressed as DOM node.
   */
  public function getDomNode(DOMDocument $Document = NULL) {
    !isset($Document) ? $Document = new DOMDocument() : NULL;
    $Element = $Document->createElement('testResult');
    $Element->setAttribute('functionName', $this->getFunctionName());
    $Element->setAttribute('testResult', $this->getTestResult());
    if ('false' == $this->getTestResult()) {
      $cData = $Document->createCDATASection($this->getMessage());
      $Element->appendChild($cData);
    }
    return $Element;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_UnitTest_ResultInterface
   ********************************************************************************/
  /** 
   * @return AblePolecat_Data_Primitive_Scalar_String Name of class subject of test.
   */
  public function getClassName() {
    return $this->className;
  }
  
  /**
   * Returns name of expected result data type.
   *
   * Should be the same as passing return value to AblePolecat_Data::getDataTypeName().
   * Either the name of a primitive data type or a class name.
   *
   * @return string.
   */
  public function getDataTypeName() {
    return $this->returnDataType;
  }
  
  /**
   * @return AblePolecat_Data_Primitive_Scalar_String Name of function subject of test.
   */
  public function getFunctionName() {
    return $this->functionName;
  }
  
  /**
   * @return AblePolecat_Data_Primitive_Scalar_String Message explaining why test failed.
   */
  public function getMessage() {
    return $this->message;
  }
  
  /**
   * @return AblePolecat_Data_Primitive_Scalar_Boolean TRUE if test passed otherwise FALSE.
   */
  public function getTestResult() {
    return $this->testResult;
  }
  
  /**
   * @return AblePolecat_Data_Primitive_Scalar_Boolean TRUE if result is OUTPUT of a test, otherwise FALSE.
   */
  public function isOutput() {
    return $this->isOutput;
  }
  
  /** 
   * @param string $className.
   */
  public function setClassName($className) {
    $this->className = AblePolecat_Data_Primitive_Scalar_String::typeCast($className);
  }
  
  /**
   * Set name of expected result data type.
   *
   * @param string $returnDataType.
   */
  public function setDataTypeName($returnDataType) {
    $this->returnDataType = AblePolecat_Data_Primitive_Scalar_String::typeCast($returnDataType);
  }
  
  /** 
   * @param string $functionName.
   */
  public function setFunctionName($functionName) {
    $this->functionName = AblePolecat_Data_Primitive_Scalar_String::typeCast($functionName);
  }
  
  /**
   * Set test result to fail.
   *
   * @param AblePolecat_UnitTest_Exception $Exception thrown by test method.
   */
  public function setFail(AblePolecat_UnitTest_Exception $Exception) {
    $this->isOutput = AblePolecat_Data_Primitive_Scalar_Boolean::typeCast(TRUE);
    $this->message = $Exception->getMessage();
  }
  
  /**
   * Set test result to pass.
   */
  public function setPass() {
    $this->isOutput = AblePolecat_Data_Primitive_Scalar_Boolean::typeCast(TRUE);
    $this->testResult = AblePolecat_Data_Primitive_Scalar_Boolean::typeCast(TRUE);
    $this->message = NULL;
  }
  
  /********************************************************************************
   * Class functions.
   ********************************************************************************/
  
  public function __construct() {
    $this->className = NULL;
    $this->functionName = NULL;
    $this->isOutput = AblePolecat_Data_Primitive_Scalar_Boolean::typeCast(FALSE);
    $this->message = NULL;
    $this->testResult = AblePolecat_Data_Primitive_Scalar_Boolean::typeCast(FALSE);
    $this->returnDataType = 'null';
  }
}