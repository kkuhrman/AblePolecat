<?php
/**
 * @file      polecat/core/Debug.php
 * @brief     A bouquet of useful static helper functions.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.7.0
 */
 
class AblePolecat_Debug {
  /**
   * die and vomit on screen.
   *
   * @param mixed $object A DOM object to examine.
   */
  public static function kill($object = NULL) {
    
    // global $Clock;
    
    $backtrace = self::getFunctionCallBacktrace(2);
    $message = '<p>' . __METHOD__ . ' called. context: ';
    if (isset($backtrace['class'])) {
      $message .= $backtrace['class'];
      isset($backtrace['type']) ? $message .= $backtrace['type'] : $message .= '.';
      // isset($backtrace['type']) ? $message .= $backtrace['type'] : $message .= '.';
      isset($backtrace['function']) ? $message .= $backtrace['function'] : NULL;
      $message .= '<br />';
    }
    $message .= '<br />';
    isset($backtrace['line']) ? $message .= ' line ' . $backtrace['line'] : NULL;
    isset($backtrace['file']) ? $message .= ' in file ' . $backtrace['file'] : NULL;
    $message .= '</p>';
    echo $message;
    
    $properties = array(
      'DOMDocument' => array(
        'actualEncoding',
        'config',
        'doctype',
        'documentElement',
        'documentURI',
        'encoding',
        'formatOutput',
        'implementation',
        'preserveWhiteSpace',
        'recover',
        'resolveExternals',
        'standalone',
        'strictErrorChecking',
        'substituteEntities',
        'validateOnParse',
        'version',
        'xmlEncoding',
        'xmlStandalone',
        'xmlVersion',
        'nodeName',
        'nodeValue',
        'nodeType',
        'parentNode',
        'childNodes',
        'firstChild',
        'lastChild',
        'previousSibling',
        'nextSibling',
        'attributes',
        'ownerDocument',
        'namespaceURI',
        'prefix',
        'localName',
        'baseURI',
        'textContent',
      ),
      'DOMNode' => array(
        'nodeName',
        'nodeValue',
        'nodeType',
        'parentNode',
        'childNodes',
        'firstChild',
        'lastChild',
        'previousSibling',
        'nextSibling',
        'attributes',
        'ownerDocument',
        'namespaceURI',
        'prefix',
        'localName',
        'baseURI',
        'textContent',
      ),
      'DOMElement' => array(
        'schemaTypeInfo',
        'tagName',
        'nodeName',
        'nodeValue',
        'nodeType',
        'parentNode',
        'childNodes',
        'firstChild',
        'lastChild',
        'previousSibling',
        'nextSibling',
        'attributes',
        'ownerDocument',
        'namespaceURI',
        'prefix',
        'localName',
        'baseURI',
        'textContent',
      ),
    );
    
    if (isset($object) && is_object($object)) {
      $className = @get_class($object);
      if (isset($properties[$className])) {
        echo "<h2>$className</h2>";
        foreach($properties[$className] as $key => $varName) {          
          if (property_exists($object, $varName)) {
            $varValue = $object->$varName;
            $type = gettype($varValue);
            $varExport = '';
            if ($type == 'object') {
              $type = get_class($varValue);
              $varExport = 'Object';
            }
            else {
              switch ($type) {
                default:
                  $varExport = strval($varValue);
                  break;
                case 'boolean':
                  $varValue ? $varExport = 'true' : $varExport = 'false';
                  break;
              }
            }
            $out = sprintf("<pre class='xdebug-var-dump' dir='ltr'> <em>%s</em> <small>%s</small> <font color='#cc0000'>%s</font></pre>",
              $varName,
              $type,
              $varExport
            );
            echo $out;
          }
        }
      }
      else {
        var_dump($object);
      }
    }
    else {
      var_dump($object);
    }
    // print('<p><strong>stop: ' . $Clock->getElapsedTime(AblePolecat_Clock::ELAPSED_TIME_TOTAL_ACTIVE, TRUE) . '</strong></p>');
    exit(1);
  }
  
  /**
   * Debug information helper.
   */
  public static function getFunctionCallBacktrace($stackPos = NULL) {
    $backtrace = debug_backtrace();
    if (isset($stackPos) && isset($backtrace[$stackPos])) {
      //
      // @todo: this is an uncertain hack to get line # to correspond/sync with function/method and file
      //
      isset($backtrace[$stackPos - 1]['line']) ? $line = $backtrace[$stackPos - 1]['line'] : $line = $backtrace[$stackPos]['line'];
      $backtrace = $backtrace[$stackPos];
      $backtrace['line'] = $line;
    }
    else if (isset($stackPos) && ($stackPos == 'xml')) {
      $backtrace_xml = '<backtrace>';
      foreach($backtrace as $key => $frame) {
        $backtrace_xml .= sprintf("<frame id=\"%d\">", $key);
        isset($frame['file']) ? $backtrace_xml .= sprintf("<file>%s</file>", $frame['file']) : NULL;
        isset($frame['line']) ? $backtrace_xml .= sprintf("<line>%d</line>", $frame['line']) : NULL;
        isset($frame['class']) ? $backtrace_xml .= sprintf("<class>%s</class>", $frame['class']) : NULL;
        isset($frame['function']) ? $backtrace_xml .= sprintf("<function>%s</function>", $frame['function']) : NULL;
        $backtrace_xml .= '</frame>';
      }
      $backtrace_xml .= '</backtrace>';
      $backtrace = $backtrace_xml;
    }
    return $backtrace;
  }
  
  final protected function __construct() {}
}