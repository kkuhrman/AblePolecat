<?php
/**
 * @file      polecat/core/Registry/Entry.php
 * @brief     Encapsulates a record in one of the Able Polecat core registries.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Dynamic.php')));
require_once(ABLE_POLECAT_CORE . DIRECTORY_SEPARATOR . 'DynamicObject.php');

interface AblePolecat_Registry_EntryInterface 
  extends AblePolecat_AccessControl_Article_DynamicInterface, AblePolecat_DynamicObjectInterface {
  
  /**
   * Generate registry entry data from project configuration file element(s).
   *
   * @param DOMNode $Node Registry entry data from project configuration file.
   *
   * @return Array[AblePolecat_Registry_EntryInterface].
   */
  public static function import(DOMNode $Node);
  
  /**
   * Create DOMNode and populate with registry entry data .
   *
   * @param DOMDocument $Document Registry entry will be exported to this DOM Document.
   * @param DOMElement $Parent Registry entry will be appended to this DOM Element.
   *
   * @return DOMElement Exported element or NULL.
   */
  public function export(DOMDocument $Document, DOMElement $Parent);
  
  /**
   * Fetch registration record given by id.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return AblePolecat_Registry_EntryInterface.
   */
  public static function fetch($primaryKey);
  
  /**
   * @return int Typically the last modified date of the object source file.
   */
  public function getLastModifiedTime();
  
  /**
   * Returns name(s) of field(s) uniquely identifying records for encapsulated table.
   *
   * @return Array[string].
   */
  public static function getPrimaryKeyFieldNames();
  
  /**
   * Update or insert registration record.
   *
   * If the encapsulated registration exists, based on id property, it will be updated
   * to reflect object state. Otherwise, a new registration record will be created.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @return AblePolecat_Registry_EntryInterface or NULL.
   */
  public function save(AblePolecat_DatabaseInterface $Database = NULL);
  
  /**
   * Validates given value against primary key schema.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return mixed Value of primary key if valid, otherwise FALSE.
   */
  public static function validatePrimaryKey($primaryKey = NULL);
}

abstract class AblePolecat_Registry_EntryAbstract extends AblePolecat_DynamicObjectAbstract implements AblePolecat_Registry_EntryInterface {
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * Scope of operation.
   *
   * @return string SYSTEM.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_DynamicInterface.
   ********************************************************************************/
   
  /**
   * @return UUID Universally unique identifier of registry object.
   */
  public function getId() {
    return $this->getPropertyValue('id');
  }
  
  /**
   * @return string Common name of registry object.
   */
  public function getName() {
    return $this->getPropertyValue('name');
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Registry_EntryInterface.
   ********************************************************************************/
  
  /**
   * @return int Typically the last modified date of the object source file.
   */
  public function getLastModifiedTime() {
    return $this->getPropertyValue('lastModifiedTime');
  }
  
  /**
   * Validates given value against primary key schema.
   *
   * @param mixed $primaryKey Array[fieldName=>fieldValue] for compound key or value of PK.
   *
   * @return mixed Value of primary key if valid, otherwise FALSE.
   */
  public static function validatePrimaryKey($primaryKey = NULL) {
    
    $validPrimaryKeyValue = FALSE;
    
    if (isset($primaryKey) && is_array($primaryKey)) {
        if (isset($primaryKey['id'])) {
          $validPrimaryKeyValue = $primaryKey['id'];
        }
        else {
          if (isset($primaryKey[0])) {
            $validPrimaryKeyValue = $primaryKey[0];
          }  
        }
    }
    else {
      if (is_string($primaryKey)) {
        $validPrimaryKeyValue = $primaryKey;
      }
    }
    return $validPrimaryKeyValue;
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
   
  /**
   * @return string UUID generated by project database or NULL.
   */
  public static function generateUUID() {
    
    $UUID = NULL;
    $sql = __SQL(array('encloseObjectNames' => FALSE))->select('UUID()');
    $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
    if ($CommandResult->success()) {
      $Result = $CommandResult->value();
      if (isset($Result[0])) {
        $UUID = $Result[0]['UUID()'];
      }
    }
    return $UUID;
  }
  
  /**
   * Find <polecat:resourceGroups> element within registry element.
   *
   * @param DOMElement $RegistryElement
   *
   * @return DOMElement or NULL.
   */
  protected static function findResourceGroups(DOMElement $RegistryElement) {
    
    $ResourceGroupsNode = NULL;
    
    if ($RegistryElement->hasChildNodes()) {
      foreach($RegistryElement->childNodes as $key => $Node) {
        if ($Node->nodeName == 'polecat:resourceGroups') {
          $ResourceGroupsNode = $Node;  
        }
      }
    }
    return $ResourceGroupsNode;
  }
   
  /**
   * Extract ids of resources within registry element.
   * 
   * Function will extract resources assigned to each group and return an Array
   * of groups, along with group attributes.
   *
   * @param DOMElement $ResourceGroupsNode
   *
   * @return Array Ids of embedded resources.
   */
  protected static function importResourceGroups(DOMElement $ResourceGroupsNode) {
    
    $resourceGroups = array();
    
    if (is_a($ResourceGroupsNode, 'DOMElement') && ($ResourceGroupsNode->tagName == 'polecat:resourceGroups') && $ResourceGroupsNode->hasChildNodes()) {
      foreach($ResourceGroupsNode->childNodes as $key => $ResourceGroupNode) {
        //
        // Pass over DOMText and other white space nodes...
        //
        if (is_a($ResourceGroupNode, 'DOMElement') && ($ResourceGroupNode->tagName == 'polecat:resourceGroup') && $ResourceGroupNode->hasChildNodes()) {
          $attributes = NULL;
          $resourceGroupId = $key;
          if ($ResourceGroupNode->hasAttributes()) {
            $attributes = $ResourceGroupNode->attributes;
            $id = $attributes->getNamedItem('id');
            if (isset($id)) {
              $resourceGroupId = $id->value;
            }
          }
          $resourceGroups[$resourceGroupId] = array('attributes' => $attributes, 'resources' => array());
          
          //
          // The resource group id corresponds to the classId of one or more 
          // registered resources. Retrieve those now.
          //
          $sql = __SQL()->
            select(
              'id', 
              'name')->
            from('resource')->
            where(sprintf("`classId` = '%s'", $resourceGroupId));
          $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
          if ($CommandResult->success() && is_array($CommandResult->value())) {
            //
            // Create a look up table of registered resources ordered by name (URI path).
            //
            $registeredResources = array();
            foreach($CommandResult->value() as $key => $Record) {
              isset($Record['id']) ? $id = $Record['id'] : $id = NULL;
              isset($Record['name']) ? $name = $Record['name'] : $name = NULL;
              if (isset($id) && isset($name)) {
                if (!isset($registeredResources[$name])) {
                  $registeredResources[$name] = $id;
                }
                else {
                  //
                  // @todo: enforce uniqueness of 'name' attribute of resource 
                  // element encapsulated by resourceGroup element through XML
                  // schema.
                  //
                  $message = sprintf("'name' attribute of resource element encapsulated by resourceGroup element must be unique. Multiple resources assigned '%s' in resourceGroup %s.",
                    $name,
                    $resourceGroupId
                  );
                  AblePolecat_Command_Chain::triggerError($message);
                }
              }
            }
            
            foreach($ResourceGroupNode->childNodes as $key => $ResourceNode) {
              if (is_a($ResourceNode, 'DOMElement') && ($ResourceNode->tagName == 'polecat:resource')) {
                //
                // Look up registered resource by name (URI path).
                //
                $resourceName = $ResourceNode->getAttribute('name');
                isset($registeredResources[$resourceName]) ? $resourceId = $registeredResources[$resourceName] : $resourceId = NULL;
                if (isset($resourceId)) {
                  $resourceGroups[$resourceGroupId]['resources'][$resourceId] = $ResourceNode;
                }
              }
            }
          }
        }
      }
    }
    return $resourceGroups;
  }
  
  /**
   * Execute DML to save registry entry to database.
   *
   * @param AblePolecat_QueryLanguage_Statement_Sql_Interface $sql.
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   *
   * @return int Number of records effected.
   */
  protected function executeDml(AblePolecat_QueryLanguage_Statement_Sql_Interface $sql,
    AblePolecat_DatabaseInterface $Database = NULL) {
    $Result = NULL;
    if (isset($Database)) {
      $Result = $Database->execute($sql);
    }
    else {
      $CommandResult = AblePolecat_Command_Database_Query::invoke(AblePolecat_AccessControl_Agent_System::wakeup(), $sql);
      if ($CommandResult->success()) {
        $Result = $CommandResult->value();
      }
    }
    if (isset($Result) && isset($Result['recordsEffected'])) {
      $Result = $Result['recordsEffected'];
    }
    return $Result;
  }
  
  /**
   * Extends __construct().
   *
   * Sub-classes should override to initialize arguments.
   */
  protected function initialize() {
    $this->lastModifiedTime = 0;
  }
}