<?php
/**
 * @file      polecat/core/Database/Schema.php
 * @brief     Encapsulates the Able Polecat database schema.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */
 
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'AccessControl', 'Article', 'Static.php')));
require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Database', 'Pdo.php')));

interface AblePolecat_Database_SchemaInterface extends AblePolecat_AccessControl_Article_StaticInterface {
  /**
   * Install current schema on existing Able Polecat database.
   *
   * @param AblePolecat_Database_PdoInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public static function install(AblePolecat_Database_PdoInterface $Database);
}

class AblePolecat_Database_Schema implements AblePolecat_Database_SchemaInterface {
  
  /**
   * Article Constants.
   */
  const UUID = '0ba2b3f8-b226-11e4-976e-0050569e00a2';
  const NAME = '0.7.0';
  
  /**
   * Schema defaults.
   */
  const DEFAULT_ENGINE  = 'InnoDB';
  const DEFAULT_CHARSET = 'latin1';
  
  /********************************************************************************
   * Implementation of AblePolecat_AccessControl_ArticleInterface.
   ********************************************************************************/
  
  /**
   * General purpose of object implementing this interface.
   *
   * @return string.
   */
  public static function getScope() {
    return 'RESOURCE';
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
   * Install current schema on existing Able Polecat database.
   *
   * @param AblePolecat_Database_PdoInterface $Database Handle to existing database.
   *
   * @throw AblePolecat_Database_Exception if install fails.
   */
  public static function install(AblePolecat_Database_PdoInterface $Database) {
    
    $schemaFileName = sprintf("polecat-database-%s.xml", self::getName());
    $schemaFilePath = implode(DIRECTORY_SEPARATOR, array(dirname(ABLE_POLECAT_CORE), 'etc', 'polecat', 'database', $schemaFileName));
    $schemaFile = new DOMDocument();
    $schemaFile->load($schemaFilePath);
    $DbNodes = AblePolecat_Dom::getElementsByTagName($schemaFile, 'table');
    foreach($DbNodes as $key => $Node) {
      // AblePolecat_Debug::kill($Node);
      $sql = self::getDdl($Node);
    }
    AblePolecat_Debug::kill($sql);
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
   * @throw AblePolecat_Database_Exception if node does not translate to DDL.
   */
  protected static function getDdl(DOMNode $Node) {
    
    $sql = '';
    
    //
    // Name of table.
    //
    $tableName = $Node->getAttribute('name');
    if ($tableName === '') {
      throw new AblePolecat_Database_Exception('Database schema element `table` must contain `name` attribute.');
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
              throw new AblePolecat_Database_Exception('Database schema element `field` must contain `name` attribute.');
            }
            $columnDef = "`$fieldName`";
            
            //
            // Field type.
            //
            $fieldType = $fieldNode->getAttribute('type');
            if ($fieldType === '') {
              throw new AblePolecat_Database_Exception('Database schema element `field` must contain `type` attribute.');
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
                throw new AblePolecat_Database_Exception('Database schema element `field` attribute `size` must be numeric.');
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
            // Character set.
            //
            $fieldCharset = $fieldNode->getAttribute('charset');
            if ($fieldCharset === '') {
              $fieldCharset = NULL;
            }
            
            $fieldValue = NULL;
            $fieldComment = NULL;
            if ($fieldNode->hasChildNodes()) {
              foreach($fieldNode->childNodes as $key => $fieldChildNode) {
                switch ($fieldChildNode->nodeName) {
                  default:
                    break;
                  case 'polecat:fieldValue':
                    $columnDef .= sprintf(" DEFAULT '%s'", $fieldChildNode->nodeValue);
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
              throw new AblePolecat_Database_Exception('Database schema element `index` must contain `type` attribute.');
            }
            else {
              $indexDef = $indexType;
              if ($indexType != 'PRIMARY KEY') {
                $indexName = $indexNode->getAttribute('name');
                if ($indexName === '') {
                  throw new AblePolecat_Database_Exception('Database schema element `index` must contain `name` attribute if it is not PRIMARY KEY.');
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
                      throw new AblePolecat_Database_Exception('Database schema element `indexField` must contain `name` attribute.');
                    }
                    $indexFields[] = "`$indexFieldName`";
                    break;
                }
              }
            }
            if (0 === count($indexFields)) {
              throw new AblePolecat_Database_Exception('Database table index definition must contain at least one field.');
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
      throw new AblePolecat_Database_Exception("Database table definition for `$tableName` must contain at least one field.");
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
    AblePolecat_Debug::kill($sql);
    return $sql;
  }
}