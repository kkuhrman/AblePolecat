<?php
/**
 * @file      polecat/core/Database/Schema.php
 * @brief     Encapsulates the Able Polecat database schema.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.2
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Static.php')));

interface AblePolecat_Database_SchemaInterface extends AblePolecat_AccessControl_Article_StaticInterface {
  
  /**
   * Get DDL statements for table objects.
   *
   * @return Array.
   */
  public function getTableDefinitions();
  
  /**
   * Install current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   */
  public static function install(AblePolecat_DatabaseInterface $Database);
  
  /**
   * Update current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   */
  public static function update(AblePolecat_DatabaseInterface $Database);
}

class AblePolecat_Database_Schema implements AblePolecat_Database_SchemaInterface {
  
  /**
   * Article Constants.
   */
  const UUID = '0ba2b3f8-b226-11e4-976e-0050569e00a2';
  const NAME = 'polecat';
  
  /**
   * Schema defaults.
   */
  const DEFAULT_ENGINE  = 'InnoDB';
  const DEFAULT_CHARSET = 'latin1';
  
  /**
   * @var AblePolecat_Database_Schema Instance of schema singleton.
   */
  private static $Schema;
  
  /**
   * @var Array Table DDL.
   */
  private $tableDdl;
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'SYSTEM';
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_Article_StaticInterface.
   ********************************************************************************/
  
  /**
   * Return unique, system-wide identifier for security constraint.
   *
   * @return string Constraint identifier.
   */
  public static function getId() {
    return self::UUID;
  }
  
  /**
   * Return common name for security constraint.
   *
   * @return string Constraint name.
   */
  public static function getName() {
    return self::NAME;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_Database_SchemaInterface.
   ********************************************************************************/
  
  /**
   * Get DDL statements for table objects.
   *
   * @return Array.
   */
  public function getTableDefinitions() {
    return $this->tableDdl;
  }
   
  /**
   * Install current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   */
  public static function install(AblePolecat_DatabaseInterface $Database) {
    
    if (!isset(self::$Schema)) {
      //
      // Create instance of schema class.
      //
      self::$Schema = new AblePolecat_Database_Schema();
      
      //
      // Load schema file.
      //
      $Agent = AblePolecat_AccessControl_Agent_User_System::wakeup();
      $schemaFilePath = AblePolecat_Mode_Config::getEnvironmentVariable($Agent, AblePolecat_Mode_Config::VAR_CONF_PATH_DBSCHEMA);
      $schemaFile = new DOMDocument();
      $schemaFile->load($schemaFilePath);
      
      //
      // Create DDL statements for table objects.
      //
      $DbNodes = AblePolecat_Dom::getElementsByTagName($schemaFile, 'table');
      foreach($DbNodes as $key => $Node) {
        self::$Schema->tableDdl[] = self::getTableDdl($Node);
      }
      
      //
      // Pass schema object to database object to complete installation.
      //
      $Database->install(self::$Schema);
    }
  }
  
  /**
   * Update current schema on existing Able Polecat database.
   *
   * @param AblePolecat_DatabaseInterface $Database Handle to existing database.
   */
  public static function update(AblePolecat_DatabaseInterface $Database) {
    
  }
  
  /********************************************************************************
   * Helper functions.
   ********************************************************************************/
  
  /**
   * Parse DOM node encapsulating DDL and return statement as string.
   *
   * @param DOMNode $Node
   *
   * @return string DDL
   */
  protected static function getTableDdl(DOMNode $Node) {
    
    $sql = '';
    
    //
    // Name of table.
    //
    $tableName = $Node->getAttribute('name');
    if ($tableName === '') {
      AblePolecat_Command_Chain::triggerError('Database schema element `table` must contain `name` attribute.');
    }
    
    //
    // Engine.
    //
    $engine = $Node->getAttribute('engine');
    if ($engine === '') {
      $engine = self::DEFAULT_ENGINE;
    }
    
    //
    // Default character set.
    //
    $charset = $Node->getAttribute('charset');
    if ($charset === '') {
      $charset = self::DEFAULT_CHARSET;
    }
    
    //
    // Auto-increment.
    //
    $auto = $Node->getAttribute('auto');
    if ($auto === '') {
      $auto = NULL;
    }
    
    $fieldList = array();
    $indexList = array();
    
    foreach($Node->childNodes as $key => $childNode) {
      switch ($childNode->nodeName) {
        default:
          break;
        case 'polecat:fields':
          foreach($childNode->childNodes as $key => $fieldNode) {
            
            if (!is_a($fieldNode, 'DOMElement')) {
              continue;
            }
            
            $columnDef = '';
            //
            // Field name.
            //
            $fieldName = $fieldNode->getAttribute('name');
            if ($fieldName === '') {
              AblePolecat_Command_Chain::triggerError('Database schema element `field` must contain `name` attribute.');
            }
            $columnDef = "`$fieldName`";
            
            //
            // Field type.
            //
            $fieldType = $fieldNode->getAttribute('type');
            if ($fieldType === '') {
              AblePolecat_Command_Chain::triggerError('Database schema element `field` must contain `type` attribute.');
            }
            
            //
            // Field size.
            //
            $fieldSize = $fieldNode->getAttribute('size');
            if ($fieldSize != '') {
              if (is_numeric($fieldSize)) {
                $fieldType .= sprintf("(%s)", $fieldSize);
              }
              else {
                AblePolecat_Command_Chain::triggerError('Database schema element `field` attribute `size` must be numeric.');
              }
            }
            $columnDef .= " $fieldType";
            
            //
            // NULL
            //
            $fieldNull = $fieldNode->getAttribute('null');
            if ($fieldNull === 'NOT') {
              $columnDef .= ' NOT NULL';
            }
            
            //
            // Auto increment.
            //
            $fieldAuto = $fieldNode->getAttribute('auto');
            if ($fieldAuto === '0') {
              $columnDef .= ' AUTO_INCREMENT';
            }
            
            $fieldValue = NULL;
            $fieldComment = NULL;
            if ($fieldNode->hasChildNodes()) {
              foreach($fieldNode->childNodes as $key => $fieldChildNode) {
                switch ($fieldChildNode->nodeName) {
                  default:
                    break;
                  case 'polecat:fieldValue':
                    $fmtStr = " DEFAULT '%s'";
                    $quotes = $fieldChildNode->getAttribute('quotes');
                    if ($quotes === '0') {
                      $fmtStr = " DEFAULT %s";
                    }
                    $columnDef .= sprintf($fmtStr, $fieldChildNode->nodeValue);
                    break;
                  case 'polecat:fieldComment':
                    $columnDef .= sprintf(" COMMENT '%s'", $fieldChildNode->nodeValue);
                    break;
                }
              }
            }
            
            $fieldList[] = $columnDef;
          }
          break;
        case 'polecat:indexes':
          foreach($childNode->childNodes as $key => $indexNode) {
            if (!is_a($indexNode, 'DOMElement')) {
              continue;
            }
            
            $indexDef = '';
            
            //
            // Index type.
            //
            $indexType = $indexNode->getAttribute('type');
            $indexName = NULL;
            if ($indexType === '') {
              AblePolecat_Command_Chain::triggerError('Database schema element `index` must contain `type` attribute.');
            }
            else {
              $indexDef = $indexType;
              if ($indexType != 'PRIMARY KEY') {
                $indexName = $indexNode->getAttribute('name');
                if ($indexName === '') {
                  AblePolecat_Command_Chain::triggerError('Database schema element `index` must contain `name` attribute if it is not PRIMARY KEY.');
                }
                $indexDef .= " `$indexName`";
              }
            }
            
            $indexFields = array();
            
            if ($indexNode->hasChildNodes()) {
              foreach($indexNode->childNodes as $key => $indexChildNode) {
                switch ($indexChildNode->nodeName) {
                  default:
                    break;
                  case 'polecat:indexField':
                    $indexFieldName = $indexChildNode->getAttribute('name');
                    if ($indexFieldName === '') {
                      AblePolecat_Command_Chain::triggerError('Database schema element `indexField` must contain `name` attribute.');
                    }
                    $indexFields[] = "`$indexFieldName`";
                    break;
                }
              }
            }
            if (0 === count($indexFields)) {
              AblePolecat_Command_Chain::triggerError('Database table index definition must contain at least one field.');
            }
            $indexDef .= sprintf(" (%s)", implode(',', $indexFields));
            
            $indexList[] = $indexDef;
          }
          break;
      }
    }
    
    //
    // Cannot define a table with no fields.
    //
    if (0 === count($fieldList)) {
      AblePolecat_Command_Chain::triggerError("Database table definition for `$tableName` must contain at least one field.");
    }
    
    //
    // DDL
    //
    $sql = sprintf("CREATE TABLE `%s` (%s", 
      $tableName, 
      implode(',', $fieldList));
    if (count($indexList)) {
      $sql .= ',' . implode(',', $indexList);
    }
    $sql .= sprintf(") ENGINE=%s DEFAULT CHARSET=%s;", $engine, $charset);
    return $sql;
  }
  
  /**
   * Extends __construct().
   * Sub-classes override this method to initialize properties here.
   */
  protected function initialize() {
  }
  
  /**
   * Cached objects must be created by wakeup().
   * Initialization of sub-classes should take place in initialize().
   * @see initialize().
   */
  final protected function __construct() {
    $this->initialize();
    $this->tableDdl = array();
  }
}