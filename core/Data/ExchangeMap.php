<?php
/**
 * @file: ExchangeMap.php
 * Interface and abstract base class for all data exchange map sub-classes.
 */

interface AblePolecat_Data_ExchangeMapInterface {
  
  /**
   * Sanitize, transform, normalize/denormalize input as necessary and save as output.
   *
   * @param Array $raw_input [raw input field name] => [raw input field value]
   * @param Array $output If provided, output will be saved in this container.
   * @param Array $options NIU/reserved.
   *
   * @return Array One or more output objects/rows or FALSE if exchange failed.
   */
  public function exchange($raw_input, &$output = NULL, $options = NULL);
}

abstract class AblePolecat_Data_ExchangeMapAbstract implements AblePolecat_Data_ExchangeMapInterface {
  
  const OUTPUT_VALUE_ASSIGNED         = 'assigned';
  const OUTPUT_VALUE_FOREIGN_KEY      = 'foreign_key';
  const OUTPUT_VALUE_TRANSFORMATION   = 'transformation';
  const OUTPUT_OBJECT_PROPERTIES      = 'properties';
  const OUTPUT_OBJECT_SOURCE          = 'output_source';
  const OUTPUT_OBJECT_VALUE           = 'output_value';
  const FK_NAME                       = 'foreign_key_name';
  const FK_VALUE                      = 'foreign_key_value';
  
  /**
   * @var Container for Exchange Map.
   */
  private $outputs;
  
  /**
   * @var Maps input field name to transformation class name.
   */
  private $fieldMap;
  
  /**
   * @var Container for raw input transformers.
   */
  private $inputs;
  
  /**
   * @var Output object reference (class, table name etc).
   */
  private $objectRef;
  
  /**
   * Sub-classes must define exchange map here.
   */
  abstract protected function initialize();
  
  /**
   * Add an object as part of output.
   *
   * @return scalar Assigned key of object.
   */
  protected function addOutputObject($key = NULL) {
    
    $defKey = FALSE;
    (isset($key) && is_scalar($key)) ? $defKey = $key : $defKey = count($this->outputs);
    if (!isset($this->outputs[$defKey])) {
      $this->outputs[$defKey] = array();
    }
    return $defKey;
  }
  
  /**
   * Define property of an object as part of output.
   *
   * @param scalar $key Assigned key of object being defined.
   * @param string $name Name of field, member property, etc.
   * @param string $source Source of output value, one of OUTPUT_VALUE_ASSIGNED | OUTPUT_VALUE_FOREIGN_KEY | OUTPUT_VALUE_TRANSFORMATION.
   * @param mixed  $value Depends on the source, see note.
   *
   * NOTE: Depending on the defined source of the output data, caller must provide a value as follows:
   * OUTPUT_VALUE_ASSIGNED - a literal
   * OUTPUT_VALUE_FOREIGN_KEY - array with name of foreign key and lookup value
   * OUTPUT_VALUE_TRANSFORMATION - valid name of a field in the raw input
   */
  protected function defineOutputObjectProperty($key, $name, $source, $value) {
    
    //
    // Before we allocate any space in container we check definition
    // Throw exception if any rule is violated.    
    //
    if (isset($this->outputs[$key])) {
      if (!isset($name) && !is_string($name)) {
        $type = gettype($name);
        throw new AblePolecat_Data_ExchangeMap_Exception("Object property name must be string, $type provided",
          AblePolecat_Error::ERROR_INVALID_OBJECT_PROPERTY_NAME);
      }
      switch ($source)
      {
        default:
          //
          // complain!
          //
          throw new AblePolecat_Data_ExchangeMap_Exception("Object data source for $key.$name is not valid.",
            AblePolecat_Error::ERROR_INVALID_OBJECT_DATA_SOURCE);
          break;
        case self::OUTPUT_VALUE_ASSIGNED:
          if (!is_scalar($value)) {
            $type = gettype($value);
            throw new AblePolecat_Data_ExchangeMap_Exception("Assigned output values must be scalar literals, $type provided.",
              AblePolecat_Error::ERROR_INVALID_EXCHANGE_MAPPING);
          }
          break;
        case self::OUTPUT_VALUE_FOREIGN_KEY:
          $fkDef = isset($value[self::FK_NAME]) && 
            is_string($value[self::FK_NAME]) &&
            isset($value[self::FK_VALUE]) &&
            is_scalar($value[self::FK_VALUE]);
          if (!$fkDef) {
            throw new AblePolecat_Data_ExchangeMap_Exception("Invalid foreign key lookup definition given for output value $key.$name.",
              AblePolecat_Error::ERROR_INVALID_EXCHANGE_MAPPING);
          }
          break;
        case self::OUTPUT_VALUE_TRANSFORMATION:
          if (!isset($this->fieldMap[$value])) {
            throw new AblePolecat_Data_ExchangeMap_Exception("Input field name for output value of $key.$name is not valid.",
              AblePolecat_Error::ERROR_INVALID_EXCHANGE_MAPPING);
          }
          break;
      }
      
      //
      // All the checks passed if we got here
      //
      if (!isset($this->outputs[$key][self::OUTPUT_OBJECT_PROPERTIES])) {
        $this->outputs[$key][self::OUTPUT_OBJECT_PROPERTIES] = array();
      }
      
      if (!isset($this->outputs[$key][self::OUTPUT_OBJECT_PROPERTIES][$name])) {
        $this->outputs[$key][self::OUTPUT_OBJECT_PROPERTIES][$name] = array();
      }
      $this->outputs[$key][self::OUTPUT_OBJECT_PROPERTIES][$name][self::OUTPUT_OBJECT_SOURCE] = $source;
      $this->outputs[$key][self::OUTPUT_OBJECT_PROPERTIES][$name][self::OUTPUT_OBJECT_VALUE]  = $value;
    }
    else {
      throw new AblePolecat_Data_ExchangeMap_Exception("No object defined for key value $key",
        AblePolecat_Error::ERROR_INVALID_OBJECT_KEY);
    }
  }
  
  /**
   * Lookup record(s) corresponding to given foreign key value.
   *
   * NOTE: Sub-classes should override this only if using FK lookups.
   *
   * @param string $fk_name Name of foreign key field.
   * @param string $fk_value Value of foreign key field.
   *
   * @return Array One or more records in foreign DB corresponding to given FK or FALSE.
   */
   public function lookupRelatedRecordKeys($fk_name, $fk_value) {
    return FALSE;
   }
  
  /**
   * Set the names of the fields/properties for raw input.
   *
   * @param string $field_name Name of field in raw input.
   * @param string $txfr_class_name Name of data transformation class.
   */
  protected function mapInputField($field_name, $txfr_class_name) {
    if (!isset($this->fieldMap[$field_name]) && is_string($field_name)) {
      if (AblePolecat_Server::getClassRegistry()->isLoadable($txfr_class_name) && method_exists($txfr_class_name , 'transform')) {
        $this->fieldMap[$field_name] = $txfr_class_name;
      }
      else {
          throw new AblePolecat_Data_ExchangeMap_Exception("$txfr_class_name must implement AblePolecat_Data_ExchangeMapInterface",
            AblePolecat_Error::ERROR_INVALID_TXFR_CLASS_NAME);
        }
    }
  }
  
  /**
   * Sanitize, transform, normalize/denormalize input as necessary and save as output.
   *
   * @param Array $raw_input [raw input field name] => [raw input field value]
   * @param Array $output If provided, output will be saved in this container.
   * @param Array $options NIU/reserved.
   *
   * @return Array One or more output objects/rows or FALSE if exchange failed.
   */
  public function exchange($raw_input, &$output = NULL, $options = NULL) {
    
    $dataExchange = FALSE;
    if (isset($raw_input) && is_array($raw_input)) {
      $dataExchange = array();
      foreach($this->outputs as $key => $outputDef) {
        //
        // @todo: allow for different output types
        //
        $outputObject = new stdClass();
        foreach($outputDef[self::OUTPUT_OBJECT_PROPERTIES] as $property_name => $propertyDef) {
          switch ($propertyDef[self::OUTPUT_OBJECT_SOURCE])
          {
            default:
              break;
            case self::OUTPUT_VALUE_ASSIGNED:
              $outputObject->$property_name = $propertyDef[self::OUTPUT_OBJECT_VALUE];
              break;
            case self::OUTPUT_VALUE_FOREIGN_KEY:
              //
              // @todo: FK lookup; what to do on multiple records
              //
              $fk_name = $propertyDef[self::OUTPUT_OBJECT_VALUE][self::FK_NAME];
              $fk_value = $propertyDef[self::OUTPUT_OBJECT_VALUE][self::FK_VALUE];
              $outputObject->$property_name = $this->lookupRelatedRecordKeys($fk_name, $fk_value);
              break;
            case self::OUTPUT_VALUE_TRANSFORMATION:
              $field_name = $propertyDef[self::OUTPUT_OBJECT_VALUE];
              if (isset($raw_input[$field_name])) {
                $txfr_class_name = $this->fieldMap[$field_name];
                $txfrClass = new $txfr_class_name($raw_input[$field_name]);
                $outputObject->$property_name = $txfrClass->transform();
              }
              else {
                throw new AblePolecat_Data_ExchangeMap_Exception("No input was provided for required field $field_name.",
                  AblePolecat_Error::ERROR_INCOMPLETE_INPUT_DATA);
              }
              break;
          }
        }
        $dataExchange[] = $outputObject;
        if (isset($output) && is_array($output)) {
          $output[] = $outputObject;
        }
      }
    }
    return $dataExchange;
  }
  
  /**
   * @param string $field_name Name of field in raw input.
   *
   * @return string Name of class which will handle transformation for given field.
   */
  public function getDtxClassName($field_name) {
    $txfr_class_name = NULL;
    if (isset($this->fieldMap[$field_name])) {
      $txfr_class_name = $this->fieldMap[$field_name];
    }
    return $txfr_class_name;
  }
  
  /**
   * @return Name of output object (table, class name etc).
   */
  public function getObjectRef() {
    return $this->objectRef;
  }
  
  /**
   * Cannot override Constructor.
   * @see initialize().
   */
  final public function __construct($objectRef) {
    $this->objectRef = $objectRef;
    $this->outputs = array();
    $this->inputs = array();
    $this->fieldMap = array();
    $this->initialize();
  }
}

class AblePolecat_Data_ExchangeMap_Exception extends AblePolecat_Exception {
}