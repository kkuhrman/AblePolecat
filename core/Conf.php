<?php
/**
 * @file: Conf.php
 * Base class for Able Polecat configuration objects.
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Open.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Read.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Constraint', 'Write.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Resource', 'File.php')));

abstract class AblePolecat_ConfAbstract extends AblePolecat_AccessControl_Resource_FileAbstract {
  
  /**
   * write() parameters
   */
  const WRITE_PARAM_ELEMENT_NAME  = 'element_name';
  const WRITE_PARAM_ELEMENT_VALUE = 'element_value';
  
  /**
   * Element attribute names.
   */
  const ATTRIBUTE_AUTHOR  = 'author';  
  const ATTRIBUTE_DESC    = 'description';
  const ATTRIBUTE_NAME    = 'name';
  
  /**
   * Element names.
   */
  const CONF_NAMESPACE    = 'polecat';
  const ELEMENT_ROOT      = 'config';
  const ELEMENT_CHILDREN  = 'children';
  const ELEMENT_CLASS     = 'class';
  const ELEMENT_CLIENT    = 'client';
  const ELEMENT_DBS       = 'databases';
  const ELEMENT_DB        = 'database';
  const ELEMENT_HOST      = 'host';
  const ELEMENT_MODULE    = 'module';
  const ELEMENT_MODULES   = 'modules';
  const ELEMENT_NAME      = 'name';
  const ELEMENT_PASS      = 'pass';
  const ELEMENT_USER      = 'user';
  const ELEMENT_VALUE     = 'value';
  
  /**
   * @var Configuration data as a simple, well-formed XML document.
   */
  protected $m_SimpleXml = NULL;
  
  /**
   * Extends __construct().
   */
  protected function initialize() {
    
    //
    // Set resource constraints.
    //
    $this->setConstraint(new AblePolecat_AccessControl_Constraint_Open());
    $this->setConstraint(new AblePolecat_AccessControl_Constraint_Read());
    $this->setConstraint(new AblePolecat_AccessControl_Constraint_Write());
  }
  
  /**
   * @return SimpleXMLElement.
   */
  protected function getRootElement() {
    return $this->m_SimpleXml;
  }
  
  /**
   * @return string Configuration data as a simple, well-formed XML document or NULL.
   */
  protected function asXml() {
    
    $xml = NULL;
    if ($this->m_SimpleXml) {
      $xml = $this->m_SimpleXml->asXML();
    }
    return $xml;
  }
  
  /**
   * @return Array Configuration data as PHP array or NULL.
   */
  protected function asArray(SimpleXmlIterator $simpleXmlIterator = NULL) {
    
    $config = NULL;
    $recursive = isset($simpleXmlIterator);
    
    if (isset($this->m_SimpleXml)) {
      $config = array();    
      if (!isset($simpleXmlIterator)) {
        $simpleXmlIterator = new SimpleXmlIterator($this->asXml());
      }
      for( $simpleXmlIterator->rewind(); $simpleXmlIterator->valid(); $simpleXmlIterator->next() ) {
        
        if($simpleXmlIterator->hasChildren()) {
          $config[] = array(
            self::ELEMENT_NAME => $simpleXmlIterator->key(),
            self::ELEMENT_CHILDREN => $this->asArray($simpleXmlIterator->current()),
          );
        }
        else {
          $config[] = array(
            self::ELEMENT_NAME => $simpleXmlIterator->key(),
            self::ELEMENT_VALUE => strval($simpleXmlIterator->current())
          );
        }
      }
    }
    return $config;
  }
  
  /**
   * Helper function extracts given element from assoc array returned by asArray().
   * 
   * Follows same rules used to format config data in asArray() to iterate through 
   * array and retrieve requested element(s). This is not efficient if more than a 
   * few elements must be read from the config file at once.
   * 
   * @param Array $Config Config settings as returned by asArray().
   * @param Array $Config Config settings as returned by asArray().
   * @param string $element_name As given element['name'].
   *
   * @return Array Requested element if it exists otherwise NULL.
   * @see asArray().
   */
  public static function getArrayElement($Config, $element_name) {
    
    $element = NULL;
    if (is_array($Config)) {
      foreach($Config as $key => $value) {
        if (isset($value['name']) && ($element_name == $value['name'])) {
          $element = $value;
        }
      }
    }
    return $element;
  }
  
  /**
   * Read from an existing resource or depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking to read.
   * @param string $start Optional offset to start reading from.
   * @param string $end Optional offset to end reading at.
   *
   * @return Array Data read from resource or NULL.
   */
  public function read(AblePolecat_AccessControl_AgentInterface $Agent, $start = NULL, $end = NULL) {
    
    $Data = NULL;
    
    if ($this->hasPermission($Agent, AblePolecat_AccessControl_Constraint_Read::getId())) {
      //
      // @todo deal with offsets
      //
      if (!isset($this->m_SimpleXml) || !isset($start) || !isset($this->m_SimpleXml->$start)) {
        $Data = new SimpleXmlIterator($this->asXml(NULL));
      }
      else if (isset($this->m_SimpleXml->$start)) {
        $Data = new SimpleXmlIterator($this->m_SimpleXml->$start->asXML());
      }
    }
    return $Data;
  }
  
  /**
   * Write to a new or existing resource or depending on permissions.
   * 
   * @param AblePolecat_AccessControl_AgentInterface $agent Agent seeking to read.
   * @param AblePolecat_AccessControl_Resource_LocaterInterface $Url Existing or new resource.
   * @param mixed $data The data to write to the resource.
   *
   * @return bool TRUE if write to resource is successful, otherwise FALSE.
   */
  public function write(
    AblePolecat_AccessControl_AgentInterface $Agent, 
    AblePolecat_AccessControl_Resource_LocaterInterface $Url,
    $data
  ) {
    
    $result = FALSE;
    
    if ($this->hasPermission($Agent, AblePolecat_AccessControl_Constraint_Write::getId())) {
      if (isset($this->m_SimpleXml)) {
        $element_name = NULL;
        $element_value = NULL;
        if (isset($data) && is_array($data)) {
          isset($data[self::WRITE_PARAM_ELEMENT_NAME]) ? $element_name = $data[self::WRITE_PARAM_ELEMENT_NAME] : NULL;
          isset($data[self::WRITE_PARAM_ELEMENT_VALUE]) ? $element_value = $data[self::WRITE_PARAM_ELEMENT_VALUE] : NULL;
        }
        else if (isset($data) && is_string($data)) {
          $element_name = $data;
        }
        $result = $this->m_SimpleXml->addChild($element_name, $element_value);
      }
    }
    return $result;
  }
}