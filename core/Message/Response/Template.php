<?php
/**
 * @file      polecat/Message/Response/Template.php
 * @brief     Creates HTTP response from template stored in database and data supplied at runtime.
 *
 * @author    Karl Kuhrman
 * @copyright [BDS II License] (https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md)
 * @version   0.6.1
 */

require_once(implode(DIRECTORY_SEPARATOR, array(ABLE_POLECAT_CORE, 'Message', 'Response.php')));

class AblePolecat_Message_Response_Template extends AblePolecat_Message_ResponseAbstract {
  
  const COMMAND_INVOKER   = 'command_invoker';
  const SUBSTITUTIONS     = 'substitutions';
  
  /**
   * Standard/default template ids.
   */
  const DEFAULT_STATUS      = '78e41080-794a-11e3-981f-0800200c9a66';
  const DEFAULT_404         = '1bfc30a0-794a-11e3-981f-0800200c9a66';
  
  /**
   * @var AblePolecat_AccessControl_SubjectInterface Needed to query database.
   */
  private $CommandInvoker;
  
  /**
   * @var Array NVPs of template place holders and values to insert therein.
   */
  private $substitutions;
  
  /**
   * @var string Id of the template to load from database.
   */
  private $resource_id;
  
  /********************************************************************************
   * Implementation of AblePolecat_DynamicObjectInterface.
   ********************************************************************************/
  
  /**
   * Create a concrete instance of AblePolecat_Message_Response_Template.
   *
   * @param string $resource_id Id of the template to load from database.
   * @param Array $substitutions Values to insert into template ordered by name.
   * @param string $status_code HTTP response status code.
   * @param Array $fields Optional header fields.
   *
   * @return AblePolecat_Message_Response_Template Concrete instance of message or NULL.
   */
  public static function create() {
    
    $Response = new AblePolecat_Message_Response_Template();
    
    //
    // Unmarshall (from numeric keyed index to named properties) variable args list.
    //
    $ArgsList = AblePolecat_Message_Response_Template::unmarshallArgsList(__FUNCTION__, func_get_args());
    
    //
    // Assign properties from variable args list.
    //
    $Response->setDefaultCommandInvoker(
      $ArgsList->getArgumentValue(AblePolecat_Message_Response_Template::COMMAND_INVOKER, NULL)
    );
    $Response->setResourceId(
      $ArgsList->getArgumentValue(
        AblePolecat_Message_Response_Template::RESOURCE_ID, 
        AblePolecat_Message_Response_Template::DEFAULT_STATUS
      )
    );
    $Response->setSubstitions(
      $ArgsList->getArgumentValue(AblePolecat_Message_Response_Template::SUBSTITUTIONS, array())
    );
    $Response->setStatusCode(
      $ArgsList->getArgumentValue(AblePolecat_Message_ResponseInterface::STATUS_CODE, 200)
    );
    $Response->appendHeaderFields(
      $ArgsList->getArgumentValue(AblePolecat_Message_ResponseInterface::HEADER_FIELDS, array())
    );
    
    //
    // Load template from database.
    //
    $Invoker = $Response->getDefaultCommandInvoker();
    if (isset($Invoker)) {
      $sql = __SQL()->          
        select('mimeType', 'defaultHeaders', 'body')->
        from('template')->
        where("resourceId = '" . $Response->resource_id . "'");
      $Result = AblePolecat_Command_DbQuery::invoke($Invoker, $sql);
      if($Result->success()) {
        $Template = $Result->value();
        
        //
        // Append headers to response
        // @todo: handle conflicts/merging
        //
        $headers = unserialize($Template[0]['defaultHeaders']);
        $headers[] = $Template[0]['mimeType'];
        $Response->appendHeaderFields($headers);
        
        //
        // Insert substitutions into template body
        //
        $body = $Template[0]['body'];
        foreach($Response->substitutions as $name => $value) {
          $body = str_replace('{' . $name . '}', $value, $body);
        }
        $Response->body = $body;
      }
    }
    
    return $Response;
  }
  
  /********************************************************************************
   * Implementation of AblePolecat_OverloadableInterface.
   ********************************************************************************/
  
  /**
   * Marshall numeric-indexed array of variable method arguments.
   *
   * @param string $method_name __METHOD__ is good enough.
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
              $ArgsList->{AblePolecat_Message_Response_Template::COMMAND_INVOKER} = $value;
              break;
            case 1:
              $ArgsList->{AblePolecat_Message_ResponseInterface::RESOURCE_ID} = $value;
              break;
            case 2:
              $ArgsList->{AblePolecat_Message_Response_Template::SUBSTITUTIONS} = $value;
              break;
            case 3:
              $ArgsList->{AblePolecat_Message_ResponseInterface::HEADER_FIELDS} = $value;
              break;
            case 4:
              $ArgsList->{AblePolecat_Message_ResponseInterface::STATUS_CODE} = $value;
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
   * Default command invoker.
   *
   * @return AblePolecat_AccessControl_SubjectInterface or NULL.
   */
  protected function getDefaultCommandInvoker() {
    return $this->CommandInvoker;
  }
  
  /**
   * Sets the default command handlers (invoker/target).
   * 
   * @param AblePolecat_AccessControl_SubjectInterface $Invoker
   */
  protected function setDefaultCommandInvoker(AblePolecat_AccessControl_SubjectInterface $Invoker) {
    $this->CommandInvoker = $Invoker;
  }
  
  /**
   * Set substitution NVPs.
   *
   * @param Array NVPs of template place holders and values to insert therein.
   */
  protected function setSubstitions($substitutions) {
    
    if (isset($substitutions) && is_array($substitutions)) {
      $this->substitutions = $substitutions;
    }
  }
  
  /**
   * Set Id of template to load from database.
   *
   * @param string Id of the template to load from database.
   */
  protected function setResourceId($resource_id) {
    
    if (isset($resource_id)) {
      $this->resource_id = strval($resource_id);
    }
  }
}