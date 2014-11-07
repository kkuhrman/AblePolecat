<?php
/**
 * @file      polecat/core/Resource/Form.php
 * @brief     The very simplest of interactive (HTML) forms.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.2
 */

require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Resource.php');

class AblePolecat_Resource_Form extends AblePolecat_ResourceAbstract {
  
  /**
   * @var resource Instance of singleton.
   */
  private static $Resource;
  
  /**
   * @var string.
   */
  private $BodyFormat;
  
  /**
   * @var Array
   */
  private $formElements;
  
  /**
   * @var Array
   */
  private $textElements;
  
  /**
   * Constants.
   */
  const UUID = '7641ec47-44c4-11e4-b353-0050569e00a2';
  const NAME = AblePolecat_Message_RequestInterface::RESOURCE_NAME_FORM;
  
  /********************************************************************************
   * Implementation of AblePolecat_CacheObjectInterface
   ********************************************************************************/
  
  /**
   * Create a new instance of object or restore cached object to previous state.
   *
   * @param AblePolecat_AccessControl_SubjectInterface $Subject
   *
   * @return Instance of AblePolecat_Resource_Form
   */
  public static function wakeup(AblePolecat_AccessControl_SubjectInterface $Subject = NULL) {
    
    if (!isset(self::$Resource)) {
      self::$Resource = new AblePolecat_Resource_Form($Subject);
    }
    return self::$Resource;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * PHP magic method is run when writing data to inaccessible properties.
   *
   * @param string $name  Name of property to set.
   * @param mixed  $value Value to assign to given property.
   */
  public function __set($name, $value) {
    
    if ($name == 'Body') {
      throw new AblePolecat_Resource_Exception("Cannot define form property Body directly. @see AblePolecat_Resource_Form::addControl().");
    }
    parent::__set($name, $value);
  }
  
  /**
   * PHP magic method is utilized for reading data from inaccessible properties.
   *
   * @param string $name  Name of property to get.
   *
   * @return mixed Value of given property.
   */
  public function __get($name) {
    
    $value = NULL;
    
    if ($name == 'Body') {
      $value = sprintf($this->BodyFormat, 
        implode('',$this->textElements),
        $this->getId(),
        '', // all forms should be processed through index.php; @see addControl('input', array('type'=>'hidden'))
        implode(' ', $this->formElements)
      );
    }
    // else {
      // $value = parent::__get($name);
    // }
    return $value;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Add a control element to the form.
   * 
   * @param string $tagName Tag name of form element.
   * @param Array  $attributes Array[attribute name => attribute value]
   * @param string $elementText CDATA value of element.
   */
  public function addControl(
    $tagName, 
    $attributes,
    $elementText = NULL
  ) {
    
    $ElementText = NULL;
    
    switch ($tagName) {
      default:
        throw new AblePolecat_Resource_Exception("Element type $tagName is not supported in basic form.");
        break;
      case 'input':
        $ElementText = sprintf("<input %s/><br />", AblePolecat_Dom::expressElementAttributes($attributes, STR_PAD_RIGHT));
        break;
      case 'hidden':
        $ElementText = sprintf("<hidden %s/>", AblePolecat_Dom::expressElementAttributes($attributes, STR_PAD_RIGHT));
        break;
      case 'label':
        $ElementText = sprintf("<label%s>%s</label>", AblePolecat_Dom::expressElementAttributes($attributes), $elementText);
        break;
    }
    if (isset($ElementText)) {
      $this->formElements[] = $ElementText;
    }
  }
  
  /**
   * Add text to the top of the form.
   *
   * @param string $text Text to appear at the top of the form.
   */
  public function addText($text) {
    if (is_string($text)) {
      $this->textElements[] = sprintf("<p>%s</p>", $text);
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
    $this->setId(self::NAME);
    $this->BodyFormat = "<div><div>%s</div><div><form id=\"%s\" action=\"%s\" method=\"post\">%s<input type=\"submit\" value=\"Submit\" /></form></div></div>";
    $this->formElements = array();
    $this->textElements = array();
  }
}