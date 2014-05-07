<?php
/**
 * @file      polecat/core/MetaData.php
 * @brief     A descriptor used to request an operation on specific properties of a web resource.
 *
 * AblePolecat_MetaDataInterface is a mashup of REST, RDF and query language concepts. 
 * It's primary design goal is to strike a balance between simplicity and flexibility.
 *
 * The interface is designed with the expectation that its most common use will be to 
 * pass it as a resource request to a service client via the service bus to retrieve or
 * manipulate a specific representation of an object over the web.
 *
 * In such cases, the 'subject' would require at a minimum the UUID of the service client
 * and the named subject of the retrieval/manipulation target.
 *
 * The representation is likely the columns or properties to retrieve or manipulate and 
 * any value assignments (in the case of DML).
 *
 * The combined predicate/object is similar to a FILTER or SQL WHERE statement.
 *
 * Lastly, options define extended service capabilities such as sorting, grouping, limits,
 * offsets and so on.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.5.0
 */
 
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'DynamicObject.php');
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'Overloadable.php');
 
interface AblePolecat_MetaDataInterface extends AblePolecat_DynamicObjectInterface, AblePolecat_OverloadableInterface {
  
  const CLIENT_ID       = 'client_id';
  const SUBJECT         = 'subject';
  const PREDICATE       = 'predicate';
  const OBJECT          = 'object';
  const REPRESENTATION  = 'representation';
  const OPTIONS         = 'options';
}

class AblePolecat_MetaData extends AblePolecat_DynamicObjectAbstract implements AblePolecat_MetaDataInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a concrete instance of AblePolecat_MetaData.
   *
   * @return AblePolecat_MetaData.
   */
  public static function create() {
    
    //
    // Create a new metdata object.
    //
    $MetaData = new AblePolecat_MetaData();
    
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = self::unmarshallArgsList(__FUNCTION__, func_get_args());
    
    //
    // Assign properties from variable args list.
    //
    $MetaData->{AblePolecat_MetaDataInterface::CLIENT_ID} = 
      $ArgsList->getArgumentValue(AblePolecat_MetaDataInterface::CLIENT_ID, NULL);
    $MetaData->{AblePolecat_MetaDataInterface::SUBJECT} = 
      $ArgsList->getArgumentValue(AblePolecat_MetaDataInterface::SUBJECT, NULL);
    $MetaData->{AblePolecat_MetaDataInterface::PREDICATE} = 
      $ArgsList->getArgumentValue(AblePolecat_MetaDataInterface::PREDICATE, NULL);
    $MetaData->{AblePolecat_MetaDataInterface::OBJECT} = 
      $ArgsList->getArgumentValue(AblePolecat_MetaDataInterface::OBJECT, NULL);
    $MetaData->{AblePolecat_MetaDataInterface::REPRESENTATION} = 
      $ArgsList->getArgumentValue(AblePolecat_MetaDataInterface::REPRESENTATION, NULL);
    $MetaData->{AblePolecat_MetaDataInterface::OPTIONS} = 
      $ArgsList->getArgumentValue(AblePolecat_MetaDataInterface::OPTIONS, NULL);
    
    //
    // Return initialized object.
    //
    return $MetaData;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_OverloadableInterface.
   ********************************************************************************/
   
  /**
   * Marshall numeric-indexed array of variable method arguments.
   *
   * @param string $method_name __METHOD__ will render className::methodName; __FUNCTION__ is probably good enough.
   * @param Array $args Variable list of arguments passed to method (i.e. get_func_args()).
   * @param mixed $options Reserved for future use.
   *
   * @return Array Associative array representing [argument name] => [argument value]
   */
  public static function unmarshallArgsList($method_name, $args, $options = NULL) {
    
    $ArgsList = AblePolecat_ArgsList::create();
    
    foreach($args as $key => $value) {
      switch ($method_name) {
        default:
          break;
        case 'create':
          switch($key) {
            case 0:
              $ArgsList->{AblePolecat_MetaDataInterface::CLIENT_ID} = $value;
              break;
            case 1:
              $ArgsList->{AblePolecat_MetaDataInterface::SUBJECT} = $value;
              break;
            case 2:
              $ArgsList->{AblePolecat_MetaDataInterface::PREDICATE} = $value;
              break;
            case 3:
              $ArgsList->{AblePolecat_MetaDataInterface::OBJECT} = $value;
              break;
            case 4:
              $ArgsList->{AblePolecat_MetaDataInterface::REPRESENTATION} = $value;
              break;
            case 5:
              $ArgsList->{AblePolecat_MetaDataInterface::OPTIONS} = $value;
              break;
          }
          break;
      }
    }
    return $ArgsList;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
  }
}